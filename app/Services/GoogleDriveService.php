<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Photo;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class GoogleDriveService
{
    /**
     * Extract folder ID from various Google Drive URL formats.
     */
    public function extractFolderId(?string $url): ?string
    {
        if (empty($url)) {
            return null;
        }

        $url = trim($url);

        // Raw ID (alphanumeric, underscores, hyphens, min 15 chars)
        if (preg_match('/^[a-zA-Z0-9_\-]{15,100}$/', $url)) {
            return $url;
        }

        // Standard folder url: /drive/folders/{id} or /drive/u/0/folders/{id}
        if (preg_match('#/folders/([a-zA-Z0-9_\-]+)#', $url, $matches)) {
            return $matches[1];
        }

        // Query parameter format: ?id={id}
        if (preg_match('#[?&]id=([a-zA-Z0-9_\-]+)#', $url, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Check if Google Drive upload mechanism is configured on this server.
     */
    public function isConfigured(): bool
    {
        return $this->getServiceAccountCredentials() !== null || !empty(config('services.google.drive_webhook_url'));
    }

    /**
     * Get configured Service Account email, if available.
     */
    public function getServiceAccountEmail(): ?string
    {
        $credentials = $this->getServiceAccountCredentials();
        return $credentials['client_email'] ?? null;
    }

    /**
     * Retrieve and validate Service Account JSON credentials.
     */
    public function getServiceAccountCredentials(): ?array
    {
        // 1. Check raw JSON string in config/env
        $rawJson = config('services.google.service_account_json');
        if (!empty($rawJson)) {
            $data = json_decode($rawJson, true);
            if (is_array($data) && !empty($data['client_email']) && !empty($data['private_key'])) {
                return $data;
            }
        }

        // 2. Check JSON credentials file path candidates
        $candidates = array_filter([
            config('services.google.service_account_file'),
            storage_path('app/google-service-account.json'),
            storage_path('app/google/service-account.json'),
            base_path('google-service-account.json'),
        ]);

        foreach ($candidates as $filePath) {
            if (file_exists($filePath) && is_readable($filePath)) {
                $fileContent = file_get_contents($filePath);
                $data = json_decode($fileContent, true);
                if (is_array($data) && !empty($data['client_email']) && !empty($data['private_key'])) {
                    return $data;
                }
            }
        }

        return null;
    }

    /**
     * Synchronize event photos to the registered Google Drive folder without duplicating.
     */
    public function syncEventPhotos(Event $event, bool $force = false): array
    {
        $folderId = $event->google_drive_folder_id ?: $this->extractFolderId($event->google_drive_url);

        if (empty($folderId)) {
            $event->update([
                'google_drive_sync_status' => 'failed',
                'google_drive_last_error' => 'Format link Google Drive tidak valid atau ID folder tidak ditemukan.',
            ]);

            return [
                'success' => false,
                'requires_credentials' => false,
                'message' => 'Format link Google Drive tidak valid. Pastikan link berupa folder Google Drive yang valid.',
            ];
        }

        $credentials = $this->getServiceAccountCredentials();
        $webhookUrl = config('services.google.drive_webhook_url');

        if (!$credentials && empty($webhookUrl)) {
            $event->update([
                'google_drive_sync_status' => 'credentials_needed',
                'google_drive_last_error' => 'Kredensial Service Account atau Webhook Apps Script belum dikonfigurasi di server.',
            ]);

            return [
                'success' => false,
                'requires_credentials' => true,
                'service_account_email' => null,
                'folder_id' => $folderId,
                'message' => 'Link Google Drive tersimpan. Namun server memerlukan otorisasi Service Account atau Apps Script untuk menyalin file otomatis ke Google Drive.',
            ];
        }

        $event->update([
            'google_drive_sync_status' => 'syncing',
            'google_drive_last_error' => null,
        ]);

        $allPhotos = $event->photos()->orderBy('shot_number')->get();
        if ($allPhotos->isEmpty()) {
            $event->update([
                'google_drive_sync_status' => 'completed',
                'google_drive_synced_at' => now(),
                'google_drive_synced_count' => 0,
            ]);

            return [
                'success' => true,
                'count' => 0,
                'message' => 'Link Google Drive terhubung. Belum ada foto yang diambil pada event ini.',
            ];
        }

        // Filter only unsynced photos unless $force is true
        $alreadySyncedCount = $allPhotos->whereNotNull('google_drive_file_id')->count();
        $photosToSync = $force
            ? $allPhotos
            : $allPhotos->whereNull('google_drive_file_id');

        if ($photosToSync->isEmpty()) {
            $event->update([
                'google_drive_sync_status' => 'completed',
                'google_drive_synced_at' => now(),
                'google_drive_synced_count' => $alreadySyncedCount,
            ]);

            return [
                'success' => true,
                'count' => 0,
                'already_synced' => $alreadySyncedCount,
                'message' => "Semua {$alreadySyncedCount} foto sudah tersinkronkan di Google Drive. Tidak ada foto baru yang perlu disalin.",
            ];
        }

        $newlySyncedCount = 0;
        $failedCount = 0;
        $lastError = null;

        foreach ($photosToSync as $photo) {
            $binary = $this->getPhotoBinary($photo);
            if (!$binary) {
                $failedCount++;
                continue;
            }

            $filename = "shot_{$photo->shot_number}_" . ($photo->taken_at ? $photo->taken_at->format('Ymd_His') : date('Ymd_His')) . ".jpg";

            try {
                $fileId = null;
                if ($credentials) {
                    $fileId = $this->uploadViaServiceAccount($credentials, $folderId, $filename, $binary);
                } elseif (!empty($webhookUrl)) {
                    $fileId = $this->uploadViaWebhook($webhookUrl, $folderId, $filename, $binary);
                }

                if (!empty($fileId)) {
                    $photo->update([
                        'google_drive_file_id' => $fileId,
                        'google_drive_synced_at' => now(),
                    ]);
                    $newlySyncedCount++;
                } else {
                    $failedCount++;
                    $lastError = "Google API menolak pengunggahan file {$filename}. Pastikan folder dibagikan dengan hak akses Editor.";
                }
            } catch (\Throwable $e) {
                $failedCount++;
                $lastError = $e->getMessage();
                Log::error('Google Drive Upload Error: ' . $e->getMessage());
            }
        }

        $totalSynced = $event->photos()->whereNotNull('google_drive_file_id')->count();
        $status = ($failedCount === 0 || $totalSynced > 0) ? 'completed' : 'failed';

        $event->update([
            'google_drive_sync_status' => $status,
            'google_drive_synced_at' => now(),
            'google_drive_synced_count' => $totalSynced,
            'google_drive_last_error' => $lastError,
        ]);

        if ($newlySyncedCount > 0) {
            $message = "Berhasil menyalin {$newlySyncedCount} foto baru ke folder Google Drive!";
            if ($alreadySyncedCount > 0) {
                $message .= " ({$alreadySyncedCount} foto sebelumnya sudah ada, total {$totalSynced} foto di Drive).";
            }
            if ($failedCount > 0) {
                $message .= " ({$failedCount} file gagal disalin).";
            }

            return [
                'success' => true,
                'count' => $newlySyncedCount,
                'total_synced' => $totalSynced,
                'failed' => $failedCount,
                'message' => $message,
            ];
        }

        if ($alreadySyncedCount > 0 && $failedCount === 0) {
            return [
                'success' => true,
                'count' => 0,
                'total_synced' => $totalSynced,
                'message' => "Seluruh {$totalSynced} foto sudah tersimpan di Google Drive.",
            ];
        }

        return [
            'success' => false,
            'requires_credentials' => false,
            'message' => 'Gagal menyalin foto baru ke Google Drive: ' . ($lastError ?: 'Periksa hak akses folder.'),
        ];
    }

    /**
     * Get binary content of a photo from local disk or fallback.
     */
    protected function getPhotoBinary(Photo $photo): ?string
    {
        $disk = Storage::disk('public');
        if ($photo->file_path && $disk->exists($photo->file_path)) {
            return $disk->get($photo->file_path);
        }

        // Demo fallback attachment
        $attachmentMap = [
            1 => 'IMG_2820.JPG',
            2 => 'IMG_2821.JPG',
            3 => 'IMG_2823.JPG',
            4 => 'IMG_2825.JPG',
            5 => 'IMG_2831.JPG',
            6 => 'IMG_2832.JPG',
        ];

        $filename = $attachmentMap[((abs($photo->shot_number - 1)) % 6) + 1] ?? 'IMG_2820.JPG';
        $fallback = base_path('attachment/' . $filename);

        if (file_exists($fallback)) {
            return file_get_contents($fallback);
        }

        return null;
    }

    /**
     * Upload binary image to Google Drive folder using Service Account via Drive v3 API.
     * Returns Google Drive file ID on success, or null on failure.
     */
    protected function uploadViaServiceAccount(array $credentials, string $folderId, string $filename, string $binaryContent): ?string
    {
        $token = $this->getAccessToken($credentials);

        // 1. Check if a file with this name already exists in the target folder
        try {
            $escapedFilename = addcslashes($filename, "'\\");
            $query = "name = '{$escapedFilename}' and '{$folderId}' in parents and trashed = false";
            $search = Http::withHeaders(['Authorization' => 'Bearer ' . $token])
                ->timeout(15)
                ->get('https://www.googleapis.com/drive/v3/files', [
                    'q' => $query,
                    'fields' => 'files(id, name)',
                    'pageSize' => 1,
                ]);

            if ($search->successful()) {
                $files = $search->json('files');
                if (!empty($files) && !empty($files[0]['id'])) {
                    // File already exists in Google Drive, return its ID without uploading duplicate!
                    return $files[0]['id'];
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Google Drive existence query error: ' . $e->getMessage());
        }

        // 2. Upload file
        $metadata = [
            'name' => $filename,
            'parents' => [$folderId],
        ];

        $boundary = '-------' . Str::random(24);
        $delimiter = "\r\n--" . $boundary . "\r\n";
        $closeDelimiter = "\r\n--" . $boundary . "--";

        $multipartBody = $delimiter
            . "Content-Type: application/json; charset=UTF-8\r\n\r\n"
            . json_encode($metadata)
            . $delimiter
            . "Content-Type: image/jpeg\r\n"
            . "Content-Transfer-Encoding: binary\r\n\r\n"
            . $binaryContent
            . $closeDelimiter;

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Content-Type' => 'multipart/related; boundary=' . $boundary,
        ])->timeout(35)->withBody($multipartBody, 'multipart/related; boundary=' . $boundary)
          ->post('https://www.googleapis.com/upload/drive/v3/files?uploadType=multipart');

        if ($response->successful()) {
            return $response->json('id') ?? 'synced';
        }

        return null;
    }

    /**
     * Upload via Google Apps Script Webhook.
     * Returns Google Drive file ID on success, or null on failure.
     */
    protected function uploadViaWebhook(string $webhookUrl, string $folderId, string $filename, string $binaryContent): ?string
    {
        $response = Http::timeout(45)->post($webhookUrl, [
            'folderId' => $folderId,
            'filename' => $filename,
            'mimeType' => 'image/jpeg',
            'base64' => base64_encode($binaryContent),
        ]);

        if (!$response->successful()) {
            Log::error("Google Apps Script Webhook HTTP {$response->status()}: " . $response->body());
            return null;
        }

        $json = $response->json();
        if (is_array($json) && isset($json['status']) && $json['status'] === 'error') {
            Log::error('Google Apps Script Webhook Error: ' . ($json['message'] ?? 'Unknown error'));
            return null;
        }

        return $json['id'] ?? 'synced';
    }

    /**
     * Exchange Service Account JWT for Google OAuth2 Access Token.
     */
    protected function getAccessToken(array $credentials): string
    {
        $cacheKey = 'gdrive_jwt_token_' . md5($credentials['client_email']);
        if ($token = Cache::get($cacheKey)) {
            return $token;
        }

        $header = rtrim(strtr(base64_encode(json_encode(['alg' => 'RS256', 'typ' => 'JWT'])), '+/', '-_'), '=');
        $now = time();
        $claims = rtrim(strtr(base64_encode(json_encode([
            'iss' => $credentials['client_email'],
            'scope' => 'https://www.googleapis.com/auth/drive',
            'aud' => $credentials['token_uri'] ?? 'https://oauth2.googleapis.com/token',
            'exp' => $now + 3600,
            'iat' => $now,
        ])), '+/', '-_'), '=');

        $signature = '';
        if (!openssl_sign("$header.$claims", $signature, $credentials['private_key'], OPENSSL_ALGO_SHA256)) {
            throw new \Exception("Gagal menandatangani JWT Service Account dengan RSA private key.");
        }
        $jwt = "$header.$claims." . rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');

        $response = Http::asForm()->timeout(15)->post($credentials['token_uri'] ?? 'https://oauth2.googleapis.com/token', [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt,
        ]);

        if (!$response->successful()) {
            $errorMsg = $response->json('error_description') ?: ($response->json('error') ?: $response->body());
            throw new \Exception("Autentikasi Google Service Account gagal: " . $errorMsg);
        }

        $token = $response->json('access_token');
        $expiresIn = (int) ($response->json('expires_in') ?: 3600);
        Cache::put($cacheKey, $token, max(60, $expiresIn - 120));

        return $token;
    }
}

