<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\EventController;
use App\Models\Event;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Home page: redirect to first active event or admin
Route::get('/', function () {
    $firstEvent = Event::where('is_active', true)->first();
    if ($firstEvent) {
        return redirect()->route('event.landing', $firstEvent->slug);
    }
    return redirect()->route('admin.dashboard');
})->name('home');

// Guest & Disposable Camera Routes
Route::prefix('e/{slug}')->group(function () {
    Route::get('/', [EventController::class, 'landing'])->name('event.landing');
    Route::get('/camera', [EventController::class, 'camera'])->name('event.camera');
    Route::post('/camera/capture', [EventController::class, 'capture'])->name('event.camera.capture');
    Route::get('/gallery', [EventController::class, 'gallery'])->name('event.gallery');
    Route::post('/session/reset', [EventController::class, 'resetSession'])->name('event.session.reset');
});

// Admin & Organizer Routes
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminController::class, 'index'])->name('dashboard');
    Route::get('/events/create', [AdminController::class, 'create'])->name('events.create');
    Route::post('/events', [AdminController::class, 'store'])->name('events.store');
    Route::get('/events/{event}/edit', [AdminController::class, 'edit'])->name('events.edit');
    Route::put('/events/{event}', [AdminController::class, 'update'])->name('events.update');
    Route::delete('/events/{event}', [AdminController::class, 'destroy'])->name('events.destroy');

    // QR Codes
    Route::get('/events/{event}/qrcodes', [AdminController::class, 'qrcodes'])->name('events.qrcodes');

    // Device Sessions & Roll Reset
    Route::get('/events/{event}/sessions', [AdminController::class, 'sessions'])->name('events.sessions');
    Route::post('/events/{event}/sessions/reset-all', [AdminController::class, 'resetAllSessions'])->name('events.sessions.reset-all');
    Route::post('/sessions/{session}/reset', [AdminController::class, 'resetSession'])->name('sessions.reset');

    // Photo moderation
    Route::delete('/photos/{photo}', [AdminController::class, 'deletePhoto'])->name('photos.destroy');

    // Storage link runner for shared hosting without SSH
    Route::get('/storage-link', function () {
        try {
            \Illuminate\Support\Facades\Artisan::call('storage:link');
            return response('Storage link created successfully!<br><br>' . nl2br(\Illuminate\Support\Facades\Artisan::output()));
        } catch (\Throwable $e) {
            return response('Storage link error: ' . $e->getMessage(), 500);
        }
    })->name('storage.link');
});

// Storage fallback route for shared hosting (Hostinger/cPanel) where symlinks are missing
Route::get('/storage/{path}', function (string $path) {
    $filePath = storage_path('app/public/' . $path);

    if (file_exists($filePath)) {
        return response()->file($filePath, [
            'Cache-Control' => 'public, max-age=31536000',
        ]);
    }

    // Fallback for demo photos if database was dumped from local without storage files
    $attachmentMap = [
        1 => 'IMG_2820.JPG',
        2 => 'IMG_2821.JPG',
        3 => 'IMG_2823.JPG',
        4 => 'IMG_2825.JPG',
        5 => 'IMG_2831.JPG',
        6 => 'IMG_2832.JPG',
    ];

    if (preg_match('/(?:photo|shot)_(\d+)_/i', $path, $matches)) {
        $shotNum = (int) $matches[1];
        if (isset($attachmentMap[$shotNum])) {
            $attachmentFile = base_path('attachment/' . $attachmentMap[$shotNum]);
            if (file_exists($attachmentFile)) {
                return response()->file($attachmentFile, [
                    'Cache-Control' => 'public, max-age=31536000',
                ]);
            }
        }
    }

    // Default sample fallback
    $sample = base_path('attachment/IMG_2820.JPG');
    if (file_exists($sample)) {
        return response()->file($sample, [
            'Cache-Control' => 'public, max-age=31536000',
        ]);
    }

    abort(404);
})->where('path', '.*')->name('storage.fallback');
