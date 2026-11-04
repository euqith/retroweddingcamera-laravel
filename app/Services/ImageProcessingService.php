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
     * @return array [file_path, thumbnail_path]
     */
    public function storeCapturedPhoto(string $imageData, string|int $eventIdentifier, int $shotNumber): array
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
}
