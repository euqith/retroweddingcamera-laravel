<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class ImageProcessingService
{
    protected ImageManager $manager;

    public function __construct()
    {
        $this->manager = new ImageManager(new Driver());
    }

    /**
     * Process and store a captured photo in a folder named after the event.
     *
     * @param string $imageData Base64 encoded, Data URI, or raw binary string
     * @param string|int $eventIdentifier Event slug or name (or legacy id)
     * @param int $shotNumber
     * @param string $filterPreset
     * @return array [file_path, thumbnail_path]
     */
    public function storeCapturedPhoto(
        string $imageData,
        string|int $eventIdentifier,
        int $shotNumber,
        string $filterPreset = 'fuji_quicksnap'
    ): array
    {
        // Decode image using Intervention Image v4
        if (str_starts_with($imageData, 'data:image')) {
            $image = $this->manager->decodeDataUri($imageData);
        } else {
            // Check if base64 or raw
            $decoded = base64_decode($imageData, true);
            $binary = ($decoded !== false && base64_encode($decoded) === $imageData) ? $decoded : $imageData;
            $image = $this->manager->decode($binary);
        }

        // Apply authentic 35mm analog film simulation curves
        $this->applyFilmPreset($image, $filterPreset);

        $uuid = Str::uuid()->toString();
        $filename = "shot_{$shotNumber}_{$uuid}.jpg";
        
        $folderSlug = Str::slug((string) $eventIdentifier);
        if (empty($folderSlug)) {
            $folderSlug = 'general';
        }
        $folder = "photos/{$folderSlug}";

        $mainPath = "{$folder}/{$filename}";
        $thumbPath = "{$folder}/thumb_{$filename}";

        // Save main image (quality 85)
        $encodedMain = (string) $image->encodeUsingFileExtension('jpg', quality: 85);
        Storage::disk('public')->put($mainPath, $encodedMain);

        // Create optimized thumbnail for fast masonry loading (max width 600px)
        $image->scaleDown(width: 600);
        $encodedThumb = (string) $image->encodeUsingFileExtension('jpg', quality: 80);
        Storage::disk('public')->put($thumbPath, $encodedThumb);

        return [
            'file_path' => $mainPath,
            'thumbnail_path' => $thumbPath,
        ];
    }

    /**
     * Apply authentic 35mm film stock simulation curves.
     * Guaranteed consistent across all mobile devices & browsers (iOS Safari, Android Chrome, etc.)
     */
    public function applyFilmPreset($image, string $preset): void
    {
        switch ($preset) {
            case 'summer_gold':
                // Kodak Gold 200 (Summer Riviera):
                // Soft golden sand highlights, pastel teal/cyan skies, gentle contrast and lifted matte shadows
                $image->contrast(14);
                $image->colorize(6, 12, -8);
                $image->gamma(1.04);
                $image->brightness(2);
                break;

            case 'lomography_color':
                // Rich cross-process toy camera: punchy contrast, deep saturated warm amber/orange-brown, rich shadows
                $image->contrast(28);
                $image->colorize(16, 4, -18);
                $image->gamma(0.90);
                break;

            case 'kodak_funsaver':
                // Warm golden amber, rich tone rolloff (ISO 800)
                $image->contrast(20);
                $image->colorize(16, 8, -12);
                $image->brightness(2);
                $image->gamma(0.96);
                break;

            case 'warm_portra':
                // Soft pastel wedding skin tones, gentle creamy rolloff (Portra 400)
                $image->contrast(12);
                $image->colorize(8, 4, -4);
                $image->gamma(1.02);
                break;

            case 'ilford_bw':
                // Classic silver gelatin high-contrast black & white (HP5 Plus 400)
                $image->grayscale();
                $image->contrast(32);
                break;

            case 'cinestill_800t':
                // Cinema tungsten: cool teal-blue shadows with warm amber highlights
                $image->contrast(22);
                $image->colorize(-8, 4, 18);
                $image->gamma(0.95);
                break;

            case 'agfa_vista':
                // Vibrant 90s party snapshot, punchy primary saturation
                $image->contrast(24);
                $image->colorize(12, 0, -6);
                break;

            case 'fuji_quicksnap':
            default:
                // Clean emerald shadows, crisp whites pop
                $image->contrast(20);
                $image->colorize(-2, 8, -4);
                break;
        }
    }
}
