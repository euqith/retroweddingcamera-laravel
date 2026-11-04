<?php

namespace App\Http\Controllers;

use App\Models\CameraSession;
use App\Models\Event;
use App\Models\Photo;
use App\Services\ImageProcessingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;
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

        if (!$token) {
            $token = 'DEV-' . strtoupper(Str::random(12));
            Cookie::queue('retro_device_token', $token, 60 * 24 * 30); // 30 days
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

        $myPhotos = $session->photos()->orderBy('shot_number', 'desc')->get()->map(function ($p) {
            return [
                'id' => $p->id,
                'shot_number' => $p->shot_number,
                'url' => $p->url,
                'thumbnail_url' => $p->thumbnail_url,
                'taken_at' => $p->taken_at->format('H:i'),
            ];
        });

        return view('events.camera', compact('event', 'session', 'deviceToken', 'myPhotos'));
    }

    /**
     * Capture & Store Photo Endpoint (AJAX/Fetch)
     */
    public function capture(string $slug, Request $request, ImageProcessingService $imageService): JsonResponse
    {
        $event = Event::where('slug', $slug)->where('is_active', true)->firstOrFail();
        $deviceToken = $this->getDeviceToken($request);
        $session = $this->getCameraSession($event, $deviceToken, $request);

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
            'image' => 'required|string',
            'camera_facing' => 'nullable|string|in:environment,user',
            'flash_enabled' => 'nullable|boolean',
            'caption' => 'nullable|string|max:255',
        ]);

        $shotNumber = $session->shots_taken + 1;

        // Process and store the image using Intervention Image in event slug folder
        $stored = $imageService->storeCapturedPhoto(
            $request->input('image'),
            $event->slug,
            $shotNumber
        );

        $photo = Photo::create([
            'event_id' => $event->id,
            'camera_session_id' => $session->id,
            'shot_number' => $shotNumber,
            'file_path' => $stored['file_path'],
            'thumbnail_path' => $stored['thumbnail_path'],
            'camera_facing' => $request->input('camera_facing', 'environment'),
            'flash_enabled' => (bool) $request->input('flash_enabled', false),
            'filter_applied' => $event->filter_preset ?? 'fuji_quicksnap',
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
     * Quick Reset Session Roll (For testing / dev)
     */
    public function resetSession(string $slug, Request $request): JsonResponse
    {
        $event = Event::where('slug', $slug)->firstOrFail();
        $deviceToken = $this->getDeviceToken($request);
        $session = $this->getCameraSession($event, $deviceToken, $request);

        $clearPhotos = (bool) $request->input('clear_photos', false);
        $session->resetRoll($clearPhotos);

        return response()->json([
            'success' => true,
            'message' => 'Roll kamera berhasil di-reset kembali ke 0/36!',
            'shots_taken' => $session->shots_taken,
            'max_shots' => $session->max_shots,
            'remaining' => $session->remaining_shots,
        ]);
    }
}

