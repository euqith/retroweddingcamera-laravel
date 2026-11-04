<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
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

