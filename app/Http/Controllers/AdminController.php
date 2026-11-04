<?php

namespace App\Http\Controllers;

use App\Models\CameraSession;
use App\Models\Event;
use App\Models\Photo;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

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
            'location' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'theme_preset' => 'required|string|in:ivory_gold,sacred_olive,classic_film',
            'filter_preset' => 'required|string|in:fuji_quicksnap,kodak_funsaver,warm_portra',
            'max_shots_per_device' => 'required|integer|min:1|max:100',
            'is_active' => 'nullable|boolean',
            'is_gallery_public' => 'nullable|boolean',
        ]);

        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['title']);
        }

        $validated['is_active'] = $request->has('is_active');
        $validated['is_gallery_public'] = $request->has('is_gallery_public');

        $event = Event::create($validated);

        return redirect()->route('admin.events.qrcodes', $event)
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
            'location' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'theme_preset' => 'required|string|in:ivory_gold,sacred_olive,classic_film',
            'filter_preset' => 'required|string|in:fuji_quicksnap,kodak_funsaver,warm_portra',
            'max_shots_per_device' => 'required|integer|min:1|max:100',
        ]);

        $validated['is_active'] = $request->has('is_active');
        $validated['is_gallery_public'] = $request->has('is_gallery_public');

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
        $cameraUrl = $event->camera_url;
        $galleryUrl = $event->gallery_url;

        // Generate SVG inline strings for instant crisp rendering
        $cameraQrSvg = QrCode::size(260)->margin(1)->generate($cameraUrl);
        $galleryQrSvg = QrCode::size(260)->margin(1)->generate($galleryUrl);

        return view('admin.events.qrcodes', compact('event', 'cameraUrl', 'galleryUrl', 'cameraQrSvg', 'galleryQrSvg'));
    }

    /**
     * View and moderate photos for a specific event
     */
    public function gallery(Event $event)
    {
        $photos = $event->photos()->latest('taken_at')->paginate(24);
        return view('admin.events.gallery', compact('event', 'photos'));
    }

    /**
     * Device Sessions & Roll Reset Manager
     */
    public function sessions(Event $event)
    {
        $sessions = $event->cameraSessions()->withCount('photos')->latest('updated_at')->paginate(20);

        return view('admin.events.sessions', compact('event', 'sessions'));
    }

    /**
     * Reset a single device's roll
     */
    public function resetSession(CameraSession $session, Request $request)
    {
        $clearPhotos = (bool) $request->input('clear_photos', false);
        $session->resetRoll($clearPhotos);

        return back()->with('success', "Roll kamera untuk perangkat {$session->device_token} berhasil di-reset ke 0/36!");
    }

    /**
     * Reset all devices for an event
     */
    public function resetAllSessions(Event $event, Request $request)
    {
        $clearPhotos = (bool) $request->input('clear_photos', false);

        foreach ($event->cameraSessions as $session) {
            $session->resetRoll($clearPhotos);
        }

        return back()->with('success', "Seluruh roll kamera perangkat pada event '{$event->title}' berhasil di-reset ke 0/36!");
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

