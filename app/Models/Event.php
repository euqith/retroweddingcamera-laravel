<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'slug',
        'event_type',
        'event_date',
        'location',
        'description',
        'theme_preset',
        'filter_preset',
        'max_shots_per_device',
        'is_active',
        'is_gallery_public',
    ];

    protected $casts = [
        'event_date' => 'date',
        'max_shots_per_device' => 'integer',
        'is_active' => 'boolean',
        'is_gallery_public' => 'boolean',
    ];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($event) {
            if (empty($event->slug)) {
                $baseSlug = Str::slug($event->title);
                $slug = $baseSlug;
                $counter = 1;
                while (static::where('slug', $slug)->exists()) {
                    $slug = $baseSlug . '-' . $counter++;
                }
                $event->slug = $slug;
            }
        });

        // Automatically clean up photo folder & files when event is deleted
        static::deleting(function ($event) {
            // 1. Delete all individual photo files and thumbnails
            foreach ($event->photos as $photo) {
                if ($photo->file_path && Storage::disk('public')->exists($photo->file_path)) {
                    Storage::disk('public')->delete($photo->file_path);
                }
                if ($photo->thumbnail_path && Storage::disk('public')->exists($photo->thumbnail_path)) {
                    Storage::disk('public')->delete($photo->thumbnail_path);
                }
            }

            // 2. Delete event's photo directory on server (Flysystem + direct filesystem)
            $slugFolder = "photos/{$event->slug}";
            Storage::disk('public')->deleteDirectory($slugFolder);
            $slugDiskPath = storage_path('app/public/photos/' . $event->slug);
            if (\Illuminate\Support\Facades\File::isDirectory($slugDiskPath)) {
                \Illuminate\Support\Facades\File::deleteDirectory($slugDiskPath);
            }

            // Also delete legacy ID folder if any
            $idFolder = "photos/{$event->id}";
            Storage::disk('public')->deleteDirectory($idFolder);
            $idDiskPath = storage_path('app/public/photos/' . $event->id);
            if (\Illuminate\Support\Facades\File::isDirectory($idDiskPath)) {
                \Illuminate\Support\Facades\File::deleteDirectory($idDiskPath);
            }

            // 3. Cascade delete child database records
            $event->photos()->delete();
            $event->cameraSessions()->delete();
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function cameraSessions(): HasMany
    {
        return $this->hasMany(CameraSession::class);
    }

    public function photos(): HasMany
    {
        return $this->hasMany(Photo::class)->latest('taken_at');
    }

    public function getCameraUrlAttribute(): string
    {
        return url('/e/' . $this->slug . '/camera');
    }

    public function getGalleryUrlAttribute(): string
    {
        return url('/e/' . $this->slug . '/gallery');
    }
}

