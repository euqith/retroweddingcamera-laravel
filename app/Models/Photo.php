<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Photo extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'camera_session_id',
        'shot_number',
        'original_path',
        'file_path',
        'thumbnail_path',
        'camera_facing',
        'flash_enabled',
        'filter_applied',
        'google_drive_file_id',
        'google_drive_synced_at',
        'caption',
        'is_visible',
        'taken_at',
    ];

    protected $casts = [
        'shot_number' => 'integer',
        'flash_enabled' => 'boolean',
        'is_visible' => 'boolean',
        'google_drive_synced_at' => 'datetime',
        'taken_at' => 'datetime',
    ];

    public static function boot()
    {
        parent::boot();

        static::deleting(function ($photo) {
            if ($photo->file_path && Storage::disk('public')->exists($photo->file_path)) {
                Storage::disk('public')->delete($photo->file_path);
            }
            if ($photo->thumbnail_path && Storage::disk('public')->exists($photo->thumbnail_path)) {
                Storage::disk('public')->delete($photo->thumbnail_path);
            }
        });
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function cameraSession(): BelongsTo
    {
        return $this->belongsTo(CameraSession::class);
    }

    public function getUrlAttribute(): string
    {
        if (str_starts_with($this->file_path, 'http')) {
            return $this->file_path;
        }
        if ($this->id) {
            return route('photo.view', ['photo' => $this->id]);
        }
        return Storage::disk('public')->url($this->file_path);
    }

    public function getThumbnailUrlAttribute(): string
    {
        if ($this->thumbnail_path && str_starts_with($this->thumbnail_path, 'http')) {
            return $this->thumbnail_path;
        }
        if ($this->id) {
            return route('photo.thumbnail', ['photo' => $this->id]);
        }
        if ($this->thumbnail_path) {
            return Storage::disk('public')->url($this->thumbnail_path);
        }
        return $this->url;
    }
}

