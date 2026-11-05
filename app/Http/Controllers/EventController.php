<?php

namespace App\Http\Controllers;

use App\Models\CameraSession;
use App\Models\Event;
use App\Models\Photo;
use App\Services\ImageProcessingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use ZipArchive;

class EventController extends Controller
{
    /**
     * Resolve or generate device token from header, query, or cookie.
     */
    protected function getDeviceToken(Request $request): string
    {
        $token = $request->header('X-Device-Token')
            ?? $request->input('device_token')
            ?? $request->cookie('retro_device_token');

        if ($token) {
            $token = preg_replace('/[^a-zA-Z0-9_\-]/', '', substr((string) $token, 0, 64));
        }

        if (!$token) {
            $token = 'DEV-' . strtoupper(Str::random(16));
            Cookie::queue(Cookie::make('retro_device_token', $token, 60 * 24 * 30, '/', null, $request->isSecure(), true, false, 'lax'));
        }

        return $token;
    }

    /**
     * Get or create a camera session for the given event and device token.
     */
    protected function getCameraSession(Event $event, string $deviceToken, Request $request): CameraSession
    {
        return CameraSession::firstOrCreate(
            [
                'event_id' => $event->id,
                'device_token' => $deviceToken,
            ],
            [
                'max_shots' => $event->max_shots_per_device ?? 36,
                'shots_taken' => 0,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]
        );
    }

    /**
     * Event Landing / Welcome Screen
     */
    public function landing(string $slug, Request $request)
    {
        $event = Event::where('slug', $slug)->where('is_active', true)->firstOrFail();
        $deviceToken = $this->getDeviceToken($request);
        $session = $this->getCameraSession($event, $deviceToken, $request);

        $photosCount = $event->photos()->where('is_visible', true)->count();
        $recentPhotos = $event->photos()
            ->where('is_visible', true)
            ->latest('taken_at')
            ->take(8)
            ->get();

        return view('events.landing', compact('event', 'session', 'deviceToken', 'photosCount', 'recentPhotos'));
    }

    /**
     * Disposable Camera Interface
     */
    public function camera(string $slug, Request $request)
    {
        $event = Event::where('slug', $slug)->where('is_active', true)->firstOrFail();
        $deviceToken = $this->getDeviceToken($request);
        $session = $this->getCameraSession($event, $deviceToken, $request);
        $cameraStatus = $event->cameraStatus(); // 'open', 'upcoming', 'ended'

        $myPhotos = $session->photos()->orderBy('shot_number', 'desc')->get()->map(function ($p) {
            return [
                'id' => $p->id,
                'shot_number' => $p->shot_number,
                'url' => $p->url,
                'thumbnail_url' => $p->thumbnail_url,
                'taken_at' => $p->taken_at->format('H:i'),
            ];
        });

        return view('events.camera', compact('event', 'session', 'deviceToken', 'myPhotos', 'cameraStatus'));
    }

    /**
     * Capture & Store Photo Endpoint (AJAX/Fetch)
     */
    public function capture(string $slug, Request $request, ImageProcessingService $imageService): JsonResponse
    {
        $event = Event::where('slug', $slug)->where('is_active', true)->firstOrFail();
        $deviceToken = $this->getDeviceToken($request);
        $session = $this->getCameraSession($event, $deviceToken, $request);

        // Check Event Duration Gate
        if (!$event->isCameraOpen()) {
            $status = $event->cameraStatus();
            $message = $status === 'upcoming'
                ? 'Kamera belum dibuka. Acara baru akan dimulai pada ' . ($event->starts_at?->translatedFormat('d M Y, H:i') ?? 'jadwal acara') . '.'
                : 'Periode memotret telah berakhir pada ' . ($event->ends_at?->translatedFormat('d M Y, H:i') ?? 'jadwal acara') . '. Silakan nikmati galeri foto!';

            return response()->json([
                'success' => false,
                'message' => $message,
                'camera_status' => $status,
            ], 403);
        }

        if ($session->isRollEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Roll film Anda sudah habis (36/36 foto). Seluruh foto Anda sudah tersimpan di galeri!',
                'shots_taken' => $session->shots_taken,
                'max_shots' => $session->max_shots,
                'remaining' => 0,
            ], 422);
        }

        $request->validate([
            'image' => ['required', 'string', 'starts_with:data:image/'],
            'camera_facing' => 'nullable|string|in:environment,user',
            'flash_enabled' => 'nullable|boolean',
            'filter_applied' => 'nullable|string|in:fuji_quicksnap,kodak_funsaver,warm_portra,ilford_bw,cinestill_800t,agfa_vista,lomography_color,summer_gold',
            'caption' => 'nullable|string|max:255',
        ]);

        $shotNumber = $session->shots_taken + 1;
        $filterApplied = $request->input('filter_applied') ?: ($event->filter_preset ?? 'fuji_quicksnap');

        // Process and store the image using Intervention Image with authentic 35mm film curve
        $stored = $imageService->storeCapturedPhoto(
            $request->input('image'),
            $event->slug,
            $shotNumber,
            $filterApplied
        );

        $photo = Photo::create([
            'event_id' => $event->id,
            'camera_session_id' => $session->id,
            'shot_number' => $shotNumber,
            'file_path' => $stored['file_path'],
            'thumbnail_path' => $stored['thumbnail_path'],
            'camera_facing' => $request->input('camera_facing', 'environment'),
            'flash_enabled' => (bool) $request->input('flash_enabled', false),
            'filter_applied' => $filterApplied,
            'caption' => $request->input('caption'),
            'is_visible' => true,
            'taken_at' => now(),
        ]);

        // Increment session counter
        $session->increment('shots_taken');
        $session->update([
            'last_shot_at' => now(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json([
            'success' => true,
            'message' => "Jepretan #{$shotNumber} berhasil tersimpan!",
            'photo' => [
                'id' => $photo->id,
                'shot_number' => $photo->shot_number,
                'url' => $photo->url,
                'thumbnail_url' => $photo->thumbnail_url,
            ],
            'shots_taken' => $session->shots_taken,
            'max_shots' => $session->max_shots,
            'remaining' => $session->remaining_shots,
            'is_roll_empty' => $session->isRollEmpty(),
        ]);
    }

    /**
     * Event Photo Gallery
     */
    public function gallery(string $slug, Request $request)
    {
        $event = Event::where('slug', $slug)->firstOrFail();
        $deviceToken = $this->getDeviceToken($request);
        $session = $this->getCameraSession($event, $deviceToken, $request);

        $filter = $request->query('filter', 'all'); // all, my_shots, selfies

        $query = $event->photos()->where('is_visible', true);

        if ($filter === 'my_shots') {
            $query->where('camera_session_id', $session->id);
        } elseif ($filter === 'selfies') {
            $query->where('camera_facing', 'user');
        }

        $photos = $query->paginate(36);
        $totalPhotos = $event->photos()->where('is_visible', true)->count();
        $guestCount = $event->cameraSessions()->count();

        return view('events.gallery', compact('event', 'session', 'photos', 'filter', 'totalPhotos', 'guestCount', 'deviceToken'));
    }

    /**
     * Direct photo streamer (immune to shared hosting symlink issues)
     */
    public function viewPhoto(Photo $photo)
    {
        $disk = Storage::disk('public');
        if ($photo->file_path && $disk->exists($photo->file_path)) {
            return response()->file($disk->path($photo->file_path), [
                'Content-Type' => 'image/jpeg',
                'Cache-Control' => 'public, max-age=31536000',
            ]);
        }

        return $this->serveDemoFallbackPhoto($photo->shot_number);
    }

    /**
     * Direct thumbnail streamer
     */
    public function viewThumbnail(Photo $photo)
    {
        $disk = Storage::disk('public');
        $path = $photo->thumbnail_path ?: $photo->file_path;
        if ($path && $disk->exists($path)) {
            return response()->file($disk->path($path), [
                'Content-Type' => 'image/jpeg',
                'Cache-Control' => 'public, max-age=31536000',
            ]);
        }

        return $this->serveDemoFallbackPhoto($photo->shot_number);
    }

    /**
     * Public Printable Table Card & QR Code (Accessible without login)
     */
    public function card(string $slug)
    {
        $event = Event::where('slug', $slug)->where('is_active', true)->firstOrFail();
        $landingUrl = $event->landing_url;
        $cameraUrl = $event->camera_url;
        $galleryUrl = $event->gallery_url;

        // Generate SVG inline strings for instant crisp rendering
        $landingQrSvg = QrCode::size(280)->margin(1)->generate($landingUrl);
        $cameraQrSvg = QrCode::size(260)->margin(1)->generate($cameraUrl);
        $galleryQrSvg = QrCode::size(260)->margin(1)->generate($galleryUrl);

        return view('events.card', compact('event', 'landingUrl', 'cameraUrl', 'galleryUrl', 'landingQrSvg', 'cameraQrSvg', 'galleryQrSvg'));
    }

    /**
     * Fallback photo if database was dumped from local without storage files
     */
    protected function serveDemoFallbackPhoto(int $shotNumber)
    {
        $attachmentMap = [
            1 => 'IMG_2820.JPG',
            2 => 'IMG_2821.JPG',
            3 => 'IMG_2823.JPG',
            4 => 'IMG_2825.JPG',
            5 => 'IMG_2831.JPG',
            6 => 'IMG_2832.JPG',
        ];

        $filename = $attachmentMap[((abs($shotNumber - 1)) % 6) + 1] ?? 'IMG_2820.JPG';
        $fallback = base_path('attachment/' . $filename);

        if (file_exists($fallback)) {
            return response()->file($fallback, [
                'Content-Type' => 'image/jpeg',
                'Cache-Control' => 'public, max-age=31536000',
            ]);
        }

        abort(404);
    }
}

