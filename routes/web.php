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
});
