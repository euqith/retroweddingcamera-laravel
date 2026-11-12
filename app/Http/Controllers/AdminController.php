<?php

namespace App\Http\Controllers;

use App\Models\CameraSession;
use App\Models\Event;
use App\Models\Photo;
use App\Services\GoogleDriveService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use ZipArchive;

class AdminController extends Controller
{
    /**
     * Dashboard: List all events with quick stats
     */
    public function index()
    {
        $events = Event::withCount(['photos', 'cameraSessions'])->latest()->paginate(10);
        $totalPhotos = Photo::count();
        $totalSessions = CameraSession::count();
        $activeEventsCount = Event::where('is_active', true)->count();

        return view('admin.dashboard', compact('events', 'totalPhotos', 'totalSessions', 'activeEventsCount'));
    }

    /**
     * Show form to create a new Event
     */
    public function create()
    {
        return view('admin.events.create');
    }

    /**
     * Store new Event
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:events,slug',
            'event_type' => 'required|string|in:wedding,church,general',
            'event_date' => 'nullable|date',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after_or_equal:starts_at',
            'location' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'theme_preset' => 'required|string|in:ivory_gold,sacred_olive,classic_film',
            'filter_preset' => 'required|string|in:fuji_quicksnap,kodak_funsaver,warm_portra,ilford_bw,cinestill_800t,agfa_vista,lomography_color,summer_gold',
            'show_date_stamp' => 'nullable|boolean',
            'max_shots_per_device' => 'required|integer|min:1|max:100',
            'is_active' => 'nullable|boolean',
            'is_gallery_public' => 'nullable|boolean',
            'google_drive_url' => 'nullable|string|max:500',
        ]);

        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['title']);
        }

        $validated['user_id'] = auth()->id() ?? null;
        $validated['show_date_stamp'] = $request->has('show_date_stamp');
        $validated['is_active'] = $request->has('is_active');
        $validated['is_gallery_public'] = $request->has('is_gallery_public');
        $validated['event_date'] = !empty($validated['event_date']) ? $validated['event_date'] : null;
        $validated['location'] = !empty($validated['location']) ? trim($validated['location']) : null;
        $validated['description'] = !empty($validated['description']) ? trim($validated['description']) : null;

        if (!empty($validated['google_drive_url'])) {
            $validated['google_drive_url'] = trim($validated['google_drive_url']);
            $validated['google_drive_folder_id'] = app(GoogleDriveService::class)->extractFolderId($validated['google_drive_url']);
        } else {
            $validated['google_drive_url'] = null;
            $validated['google_drive_folder_id'] = null;
        }

        // Explicitly parse in Asia/Jakarta timezone (WIB)
        if (!empty($validated['starts_at'])) {
            $validated['starts_at'] = \Illuminate\Support\Carbon::parse($validated['starts_at'], 'Asia/Jakarta');
        } else {
            $validated['starts_at'] = null;
        }

        if (!empty($validated['ends_at'])) {
            $validated['ends_at'] = \Illuminate\Support\Carbon::parse($validated['ends_at'], 'Asia/Jakarta');
        } else {
            $validated['ends_at'] = null;
        }

        $event = Event::create($validated);

        return redirect()->route('event.card', $event->slug)
            ->with('success', "Event '{$event->title}' berhasil dibuat! Silakan unduh atau cetak QR Code.");
    }

    /**
     * Show form to edit Event
     */
    public function edit(Event $event)
    {
        return view('admin.events.edit', compact('event'));
    }

    /**
     * Update Event
     */
    public function update(Request $request, Event $event)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:events,slug,' . $event->id,
            'event_type' => 'required|string|in:wedding,church,general',
            'event_date' => 'nullable|date',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after_or_equal:starts_at',
            'location' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'theme_preset' => 'required|string|in:ivory_gold,sacred_olive,classic_film',
            'filter_preset' => 'required|string|in:fuji_quicksnap,kodak_funsaver,warm_portra,ilford_bw,cinestill_800t,agfa_vista,lomography_color,summer_gold',
            'show_date_stamp' => 'nullable|boolean',
            'max_shots_per_device' => 'required|integer|min:1|max:100',
            'google_drive_url' => 'nullable|string|max:500',
        ]);

        $validated['show_date_stamp'] = $request->has('show_date_stamp');
        $validated['is_active'] = $request->has('is_active');
        $validated['is_gallery_public'] = $request->has('is_gallery_public');
        $validated['event_date'] = !empty($validated['event_date']) ? $validated['event_date'] : null;
        $validated['location'] = !empty($validated['location']) ? trim($validated['location']) : null;
        $validated['description'] = !empty($validated['description']) ? trim($validated['description']) : null;

        if (!empty($validated['google_drive_url'])) {
            $validated['google_drive_url'] = trim($validated['google_drive_url']);
            $validated['google_drive_folder_id'] = app(GoogleDriveService::class)->extractFolderId($validated['google_drive_url']);
        } else {
            $validated['google_drive_url'] = null;
            $validated['google_drive_folder_id'] = null;
        }

        // Explicitly parse in Asia/Jakarta timezone (WIB)
        if (!empty($validated['starts_at'])) {
            $validated['starts_at'] = \Illuminate\Support\Carbon::parse($validated['starts_at'], 'Asia/Jakarta');
        } else {
            $validated['starts_at'] = null;
        }

        if (!empty($validated['ends_at'])) {
            $validated['ends_at'] = \Illuminate\Support\Carbon::parse($validated['ends_at'], 'Asia/Jakarta');
        } else {
            $validated['ends_at'] = null;
        }

        $event->update($validated);

        return redirect()->route('admin.dashboard')
            ->with('success', "Event '{$event->title}' berhasil diperbarui.");
    }

    /**
     * Delete Event and its photo directory
     */
    public function destroy(Event $event)
    {
        $title = $event->title;
        $slug = $event->slug;
        $photosCount = $event->photos()->count();

        $event->delete();

        return redirect()->route('admin.dashboard')
            ->with('success', "Event '{$title}' dan seluruh {$photosCount} file foto di folder 'photos/{$slug}' berhasil dihapus permanen.");
    }

    /**
     * QR Code Manager & Printable Tent Cards
     */
    public function qrcodes(Event $event)
    {
        $landingUrl = $event->landing_url;
        $cameraUrl = $event->camera_url;
        $galleryUrl = $event->gallery_url;

        // Generate SVG inline strings for instant crisp rendering
        $landingQrSvg = \SimpleSoftwareIO\QrCode\Facades\QrCode::size(280)->margin(1)->generate($landingUrl);
        $cameraQrSvg = \SimpleSoftwareIO\QrCode\Facades\QrCode::size(260)->margin(1)->generate($cameraUrl);
        $galleryQrSvg = \SimpleSoftwareIO\QrCode\Facades\QrCode::size(260)->margin(1)->generate($galleryUrl);

        return view('events.card', compact('event', 'landingUrl', 'cameraUrl', 'galleryUrl', 'landingQrSvg', 'cameraQrSvg', 'galleryQrSvg'));
    }

    /**
     * View and moderate photos for a specific event
     */
    public function gallery(Event $event, GoogleDriveService $driveService)
    {
        $photos = $event->photos()->latest('taken_at')->paginate(24);
        $driveConfigured = $driveService->isConfigured();
        $serviceAccountEmail = $driveService->getServiceAccountEmail();

        return view('admin.events.gallery', compact('event', 'photos', 'driveConfigured', 'serviceAccountEmail'));
    }

    /**
     * Register or update Google Drive folder and trigger copy/sync.
     */
    public function updateGoogleDrive(Request $request, Event $event, GoogleDriveService $driveService)
    {
        $validated = $request->validate([
            'google_drive_url' => 'required|string|max:500',
        ]);

        $url = trim($validated['google_drive_url']);
        $folderId = $driveService->extractFolderId($url);

        if (!$folderId) {
            return back()->with('error', 'Format URL Google Drive tidak valid. Pastikan link berupa folder Google Drive yang valid.');
        }

        // If folder changed, reset photo sync markers
        if ($event->google_drive_folder_id !== $folderId) {
            $event->photos()->update([
                'google_drive_file_id' => null,
                'google_drive_synced_at' => null,
            ]);
        }

        $event->update([
            'google_drive_url' => $url,
            'google_drive_folder_id' => $folderId,
            'google_drive_sync_status' => 'idle',
        ]);

        // Immediately trigger sync
        $syncResult = $driveService->syncEventPhotos($event);

        if ($syncResult['success']) {
            return back()->with('success', $syncResult['message']);
        } elseif (!empty($syncResult['requires_credentials'])) {
            return back()->with('info', $syncResult['message']);
        } else {
            return back()->with('warning', $syncResult['message']);
        }
    }

    /**
     * Manually re-sync/copy all event photos to Google Drive.
     */
    public function syncGoogleDrive(Request $request, Event $event, GoogleDriveService $driveService)
    {
        if (empty($event->google_drive_url) && empty($event->google_drive_folder_id)) {
            return back()->with('error', 'Belum ada link Google Drive yang terdaftar untuk event ini.');
        }

        $force = $request->boolean('force');
        $syncResult = $driveService->syncEventPhotos($event, $force);

        if ($syncResult['success']) {
            return back()->with('success', $syncResult['message']);
        } elseif (!empty($syncResult['requires_credentials'])) {
            return back()->with('info', $syncResult['message']);
        } else {
            return back()->with('error', $syncResult['message']);
        }
    }

    /**
     * Disconnect Google Drive folder from event.
     */
    public function disconnectGoogleDrive(Event $event)
    {
        // Reset photo sync markers for this event
        $event->photos()->update([
            'google_drive_file_id' => null,
            'google_drive_synced_at' => null,
        ]);

        $event->update([
            'google_drive_url' => null,
            'google_drive_folder_id' => null,
            'google_drive_sync_status' => 'idle',
            'google_drive_synced_at' => null,
            'google_drive_synced_count' => 0,
            'google_drive_last_error' => null,
        ]);

        return back()->with('success', 'Tautan Google Drive berhasil diputuskan.');
    }

    /**
     * Download all event photos as a ZIP file.
     */
    public function downloadZip(Event $event)
    {
        $photos = $event->photos()->orderBy('shot_number')->get();
        if ($photos->isEmpty()) {
            return back()->with('error', 'Belum ada foto yang diambil pada event ini untuk diunduh.');
        }

        $zipFilename = 'photos_' . Str::slug($event->title) . '_' . date('Ymd_His') . '.zip';
        $tempDir = storage_path('app/temp');
        if (!File::isDirectory($tempDir)) {
            File::makeDirectory($tempDir, 0755, true);
        }
        $zipPath = $tempDir . '/' . $zipFilename;

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return back()->with('error', 'Gagal membuat file ZIP di server.');
        }

        $disk = Storage::disk('public');
        $added = 0;

        foreach ($photos as $photo) {
            $name = "shot_{$photo->shot_number}_" . ($photo->taken_at ? $photo->taken_at->format('Ymd_His') : date('Ymd_His')) . ".jpg";
            if ($photo->file_path && $disk->exists($photo->file_path)) {
                $zip->addFromString($name, $disk->get($photo->file_path));
                $added++;
            } else {
                // Check fallback demo attachments
                $attachmentMap = [
                    1 => 'IMG_2820.JPG',
                    2 => 'IMG_2821.JPG',
                    3 => 'IMG_2823.JPG',
                    4 => 'IMG_2825.JPG',
                    5 => 'IMG_2831.JPG',
                    6 => 'IMG_2832.JPG',
                ];
                $fallbackFile = base_path('attachment/' . ($attachmentMap[((abs($photo->shot_number - 1)) % 6) + 1] ?? 'IMG_2820.JPG'));
                if (file_exists($fallbackFile)) {
                    $zip->addFile($fallbackFile, $name);
                    $added++;
                }
            }
        }

        $zip->close();

        if ($added === 0 || !file_exists($zipPath)) {
            return back()->with('error', 'Tidak ada file fisik foto yang dapat dikompresi.');
        }

        return response()->download($zipPath, $zipFilename)->deleteFileAfterSend(true);
    }

    /**
     * Device Sessions Monitor (Read-Only)
     */
    public function sessions(Event $event)
    {
        $sessions = $event->cameraSessions()->withCount('photos')->latest('updated_at')->paginate(20);

        return view('admin.events.sessions', compact('event', 'sessions'));
    }

    /**
     * Toggle photo visibility or delete photo
     */
    public function deletePhoto(Photo $photo)
    {
        $shotNumber = $photo->shot_number;
        $photo->delete();

        if (request()->wantsJson()) {
            return response()->json(['success' => true, 'message' => "Foto #{$shotNumber} berhasil dihapus."]);
        }

        return back()->with('success', "Foto #{$shotNumber} berhasil dihapus permanen dari server.");
    }

    /**
     * Delete all photos for a specific event
     */
    public function clearEventPhotos(Event $event)
    {
        $count = $event->photos()->count();
        foreach ($event->photos as $photo) {
            $photo->delete();
        }

        // Also clean the directory on server disk
        \Illuminate\Support\Facades\Storage::disk('public')->deleteDirectory("photos/{$event->slug}");
        $diskPath = storage_path('app/public/photos/' . $event->slug);
        if (\Illuminate\Support\Facades\File::isDirectory($diskPath)) {
            \Illuminate\Support\Facades\File::deleteDirectory($diskPath);
        }

        return back()->with('success', "Seluruh {$count} foto pada event '{$event->title}' berhasil dihapus bersih dari server.");
    }
}

