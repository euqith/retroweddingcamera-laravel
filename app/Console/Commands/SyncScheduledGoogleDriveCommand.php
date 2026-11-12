<?php

namespace App\Console\Commands;

use App\Models\Event;
use App\Services\GoogleDriveService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncScheduledGoogleDriveCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gdrive:sync-scheduled 
                            {--event= : ID atau Slug event spesifik} 
                            {--force : Paksa sinkronisasi tanpa memeriksa jadwal interval}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sinkronisasi berkala foto event ke Google Drive sesuai jadwal interval yang diatur.';

    /**
     * Execute the console command.
     */
    public function handle(GoogleDriveService $driveService): int
    {
        $this->info('Memulai pengecekan jadwal sinkronisasi Google Drive...');

        if (!$driveService->isConfigured()) {
            $this->warn('Google Drive belum terkonfigurasi (Service Account atau Webhook Apps Script belum diisi di .env). Melewati proses.');
            return self::SUCCESS;
        }

        $query = Event::query()->whereNotNull('google_drive_folder_id');

        if ($eventIdOrSlug = $this->option('event')) {
            $query->where(function ($q) use ($eventIdOrSlug) {
                if (is_numeric($eventIdOrSlug)) {
                    $q->where('id', $eventIdOrSlug);
                } else {
                    $q->where('slug', $eventIdOrSlug);
                }
            });
        }

        $events = $query->get();

        if ($events->isEmpty()) {
            $this->line('Tidak ada event yang terhubung dengan Google Drive.');
            return self::SUCCESS;
        }

        $force = (bool) $this->option('force');
        $processedCount = 0;
        $syncedCount = 0;

        foreach ($events as $event) {
            $isDue = $force || $event->isGoogleDriveSyncDue();

            if (!$isDue) {
                $nextDue = $event->nextGoogleDriveSyncDueAt();
                $this->line("Event '{$event->title}' [{$event->googleDriveSyncFrequencyLabel()}]: belum jatuh tempo. (Jadwal berikutnya: " . ($nextDue ? $nextDue->timezone('Asia/Jakarta')->format('d M Y, H:i') . ' WIB' : '-') . ")");
                continue;
            }

            $this->info("Menyinkronkan event: '{$event->title}' (Folder ID: {$event->google_drive_folder_id})...");

            $result = $driveService->syncEventPhotos($event);

            $event->update([
                'google_drive_last_scheduled_sync_at' => now(),
            ]);

            $processedCount++;

            if ($result['success']) {
                $syncedCount++;
                $this->info("✓ {$result['message']}");
                Log::info("Scheduled Google Drive Sync Success for '{$event->title}': {$result['message']}");
            } else {
                $this->error("✗ {$result['message']}");
                Log::warning("Scheduled Google Drive Sync Warning for '{$event->title}': {$result['message']}");
            }
        }

        $this->info("Selesai. Diproses: {$processedCount} event, Sukses: {$syncedCount} event.");

        return self::SUCCESS;
    }
}
