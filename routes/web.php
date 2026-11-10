<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\EventController;
use App\Models\Event;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Homepage: Organizer / Admin Login Portal
Route::get('/', [AuthController::class, 'showLogin'])->name('home');
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Direct Photo Streaming Routes (Immune to symlink & hostinger permission issues)
Route::get('/photos/view/{photo}', [EventController::class, 'viewPhoto'])->name('photo.view');
Route::get('/photos/thumb/{photo}', [EventController::class, 'viewThumbnail'])->name('photo.thumbnail');

// Root shortcut to repair storage link (Requires Admin Authentication)
Route::get('/storage-link', function () {
    return redirect()->route('admin.storage.link');
})->middleware('auth');

// Guest & Disposable Camera Routes
Route::prefix('e/{slug}')->group(function () {
    Route::get('/', [EventController::class, 'landing'])->name('event.landing');
    Route::get('/camera', [EventController::class, 'camera'])->name('event.camera');
    Route::post('/camera/capture', [EventController::class, 'capture'])->name('event.camera.capture')->middleware('throttle:30,1');
    Route::get('/gallery', [EventController::class, 'gallery'])->name('event.gallery');
    Route::get('/card', [EventController::class, 'card'])->name('event.card');
    Route::get('/qrcode', [EventController::class, 'card'])->name('event.qrcode');
});

// Admin & Organizer Routes (Strictly Authenticated)
Route::prefix('admin')->name('admin.')->middleware('auth')->group(function () {
    Route::get('/', [AdminController::class, 'index'])->name('dashboard');
    Route::get('/events/create', [AdminController::class, 'create'])->name('events.create');
    Route::post('/events', [AdminController::class, 'store'])->name('events.store');
    Route::get('/events/{event}/edit', [AdminController::class, 'edit'])->name('events.edit');
    Route::put('/events/{event}', [AdminController::class, 'update'])->name('events.update');
    Route::delete('/events/{event}', [AdminController::class, 'destroy'])->name('events.destroy');

    // QR Codes
    Route::get('/events/{event}/qrcodes', [AdminController::class, 'qrcodes'])->name('events.qrcodes');

    // Separated Event Photo Gallery Manager
    Route::get('/events/{event}/gallery', [AdminController::class, 'gallery'])->name('events.gallery');
    Route::post('/events/{event}/gallery/clear', [AdminController::class, 'clearEventPhotos'])->name('events.gallery.clear');
    Route::get('/events/{event}/download-zip', [AdminController::class, 'downloadZip'])->name('events.download-zip');

    // Google Drive Integration & Sync
    Route::post('/events/{event}/google-drive', [AdminController::class, 'updateGoogleDrive'])->name('events.google-drive.update');
    Route::post('/events/{event}/google-drive/sync', [AdminController::class, 'syncGoogleDrive'])->name('events.google-drive.sync');
    Route::match(['post', 'delete'], '/events/{event}/google-drive/disconnect', [AdminController::class, 'disconnectGoogleDrive'])->name('events.google-drive.disconnect');

    // Device Sessions Monitor (Read-Only)
    Route::get('/events/{event}/sessions', [AdminController::class, 'sessions'])->name('events.sessions');

    // Photo moderation (Delete single photo)
    Route::delete('/photos/{photo}', [AdminController::class, 'deletePhoto'])->name('photos.destroy');

    // Storage link runner and auto-repair for shared hosting (Hostinger/cPanel)
    Route::get('/storage-link', function () {
        $logs = [];
        $publicStorage = public_path('storage');
        $storageAppPublic = storage_path('app/public');

        // 1. Ensure storage/app/public directory exists
        if (!file_exists($storageAppPublic)) {
            @mkdir($storageAppPublic, 0775, true);
            $logs[] = "Created directory: storage/app/public";
        }

        // 2. Remove broken/dead symlink or empty directory blocking storage link
        if (is_link($publicStorage)) {
            $currentTarget = @readlink($publicStorage);
            $logs[] = "Detected existing symlink -> " . ($currentTarget ?: 'unknown');
            @unlink($publicStorage);
            $logs[] = "Removed old symlink.";
        } elseif (file_exists($publicStorage)) {
            if (is_dir($publicStorage)) {
                $files = @scandir($publicStorage);
                if (count($files) <= 2) {
                    @rmdir($publicStorage);
                    $logs[] = "Removed empty public/storage directory to allow symlink creation.";
                } else {
                    $logs[] = "public/storage is an actual directory with " . (count($files) - 2) . " items.";
                }
            } else {
                @unlink($publicStorage);
                $logs[] = "Removed stray public/storage file.";
            }
        }

        // 3. Recreate relative symlink using Artisan with --force --relative
        try {
            \Illuminate\Support\Facades\Artisan::call('storage:link', ['--relative' => true, '--force' => true]);
            $logs[] = "Artisan: " . trim(\Illuminate\Support\Facades\Artisan::output());
        } catch (\Throwable $e) {
            $logs[] = "Artisan error: " . $e->getMessage();
            // Fallback native PHP symlink
            if (!file_exists($publicStorage) && function_exists('symlink')) {
                @symlink('../storage/app/public', $publicStorage);
                $logs[] = "Created relative symlink using native PHP symlink().";
            }
        }

        // 4. Restore demo photos from attachment/ into storage if database was dumped without storage files
        $restoredCount = 0;
        try {
            $photos = \App\Models\Photo::all();
            $attachmentDir = base_path('attachment');
            $attachmentFiles = file_exists($attachmentDir) ? \Illuminate\Support\Facades\File::files($attachmentDir) : [];

            if (count($attachmentFiles) > 0) {
                foreach ($photos as $photo) {
                    $targetFile = storage_path('app/public/' . $photo->file_path);
                    if (!file_exists($targetFile)) {
                        @mkdir(dirname($targetFile), 0775, true);
                        $shotIndex = ($photo->shot_number - 1) % count($attachmentFiles);
                        if (isset($attachmentFiles[$shotIndex])) {
                            copy($attachmentFiles[$shotIndex]->getRealPath(), $targetFile);
                            $restoredCount++;
                        }
                    }
                }
            }
            if ($restoredCount > 0) {
                $logs[] = "Restored {$restoredCount} demo photo files from attachment directory into storage.";
            }
        } catch (\Throwable $e) {
            $logs[] = "Photo sync notice: " . $e->getMessage();
        }

        $samplePhoto = \App\Models\Photo::first();
        $sampleUrl = $samplePhoto ? $samplePhoto->url : url('/storage');
        $isLinkActive = is_link($publicStorage) || file_exists($publicStorage);

        $html = "
        <div style='font-family: -apple-system, BlinkMacSystemFont, Segoe UI, Roboto, sans-serif; max-width: 620px; margin: 40px auto; padding: 28px; border: 1px solid #e2e8f0; border-radius: 16px; background: #fafafa; line-height: 1.6; color: #1e293b;'>
            <div style='display: flex; align-items: center; margin-bottom: 16px;'>
                <span style='font-size: 24px; margin-right: 10px;'>✅</span>
                <h2 style='margin: 0; color: #15803d; font-size: 20px;'>Storage Link Berhasil Diperbaiki!</h2>
            </div>
            <p style='font-size: 14px; margin-top: 0; color: #475569;'>
                Symlink <code>public/storage</code> lama telah dihapus dan dibuat ulang dengan tautan relatif (<code>../storage/app/public</code>) yang valid untuk server Hostinger.
            </p>
            <div style='background: #fff; padding: 14px; border-radius: 10px; border: 1px solid #cbd5e1; font-size: 13px; font-family: monospace;'>
                <strong>Log Proses:</strong>
                <pre style='margin: 8px 0 0 0; white-space: pre-wrap; color: #334155;'>" . implode("\n", $logs) . "</pre>
            </div>
            " . ($samplePhoto ? "
            <div style='margin-top: 18px; padding: 14px; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 10px;'>
                <p style='margin: 0 0 10px 0; font-size: 14px; font-weight: 600; color: #166534;'>Tes Tampilan Foto:</p>
                <img src='{$sampleUrl}' style='max-width: 180px; border-radius: 8px; box-shadow: 0 2px 6px rgba(0,0,0,0.1); display: block; margin-bottom: 10px;' alt='Sample' onerror=\"this.style.display='none'; document.getElementById('img-err').style.display='block';\" />
                <div id='img-err' style='display: none; color: #dc2626; font-size: 13px; margin-bottom: 8px;'>⚠️ Gambar belum tampil langsung via web server.</div>
                <a href='{$sampleUrl}' target='_blank' style='color: #2563eb; font-size: 13px; font-weight: 500; text-decoration: underline;'>Buka link gambar langsung ↗</a>
            </div>
            " : "") . "
            <div style='margin-top: 24px; display: flex; gap: 12px;'>
                <a href='/admin' style='padding: 10px 20px; background: #2563eb; color: #fff; text-decoration: none; border-radius: 8px; font-size: 14px; font-weight: 500;'>Buka Admin Dashboard</a>
                <a href='javascript:location.reload()' style='padding: 10px 20px; background: #e2e8f0; color: #334155; text-decoration: none; border-radius: 8px; font-size: 14px; font-weight: 500;'>Refresh Periksa Ulang</a>
            </div>
        </div>
        ";

        return response($html);
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
