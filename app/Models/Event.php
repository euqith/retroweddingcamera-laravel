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
        'starts_at',
        'ends_at',
        'location',
        'description',
        'theme_preset',
        'filter_preset',
        'show_date_stamp',
        'max_shots_per_device',
        'is_active',
        'is_gallery_public',
        'google_drive_url',
        'google_drive_folder_id',
        'google_drive_sync_status',
        'google_drive_synced_at',
        'google_drive_synced_count',
        'google_drive_last_error',
    ];

    protected $casts = [
        'event_date' => 'date',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'show_date_stamp' => 'boolean',
        'max_shots_per_device' => 'integer',
        'is_active' => 'boolean',
        'is_gallery_public' => 'boolean',
        'google_drive_synced_at' => 'datetime',
        'google_drive_synced_count' => 'integer',
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

    public function getLandingUrlAttribute(): string
    {
        return url('/e/' . $this->slug);
    }

    public function getCameraUrlAttribute(): string
    {
        return url('/e/' . $this->slug . '/camera');
    }

    public function getGalleryUrlAttribute(): string
    {
        return url('/e/' . $this->slug . '/gallery');
    }

    /**
     * Determine if camera can currently take photos based on event duration.
     */
    public function isCameraOpen(): bool
    {
        return $this->cameraStatus() === 'open';
    }

    /**
     * Get camera access status ('open', 'upcoming', 'ended', 'inactive').
     * Evaluated against Asia/Jakarta (WIB) timezone for accurate event scheduling.
     */
    public function cameraStatus(): string
    {
        if (!$this->is_active) {
            return 'inactive';
        }

        $tz = 'Asia/Jakarta';
        $now = now($tz);

        if ($this->starts_at) {
            $startsAt = $this->starts_at->copy()->timezone($tz);
            if ($now->lt($startsAt)) {
                return 'upcoming';
            }
        }

        if ($this->ends_at) {
            $endsAt = $this->ends_at->copy()->timezone($tz);
            if ($now->gt($endsAt)) {
                return 'ended';
            }
        }

        return 'open';
    }

    /**
     * Get visual theme styling based on theme_preset.
     */
    public function getThemeStyles(): array
    {
        return match ($this->theme_preset) {
            'sacred_olive' => [
                'name' => 'Sacred Olive (Church / Botanical)',
                'page_bg' => 'bg-[#182019]',
                'body_bg' => 'bg-[#273529]',
                'body_border' => 'border-[#1B251D]',
                'header_border' => 'border-[#C8AE7D]/35',
                'title_color' => 'text-[#FAF6F0]',
                'sub_color' => 'text-[#A2B89B]',
                'badge_style' => 'bg-[#1B251D] text-[#D4AF37] border border-[#C8AE7D]/40',
                'accent_color' => 'text-[#D4AF37]',
                'tag' => 'Sacred Church / Botanical Edition',
                'decal_icon' => '🌿',
            ],
            'classic_film' => [
                'name' => 'Classic 35mm Analog',
                'page_bg' => 'bg-[#141414]',
                'body_bg' => 'bg-[#E5A93C]',
                'body_border' => 'border-[#1f1b1a]',
                'header_border' => 'border-[#231F1E]/30',
                'title_color' => 'text-[#231F1E]',
                'sub_color' => 'text-[#4A3800]',
                'badge_style' => 'bg-[#231F1E] text-[#F2B705] border border-black',
                'accent_color' => 'text-[#C9381E]',
                'tag' => 'Vintage 35mm Single-Use Camera',
                'decal_icon' => '🎞️',
            ],
            default => [ // ivory_gold
                'name' => 'Ivory & Gold (Wedding Classic)',
                'page_bg' => 'bg-[#1F1B1A]',
                'body_bg' => 'bg-[#FAF6F0]',
                'body_border' => 'border-[#231F1E]',
                'header_border' => 'border-wedding-gold/30',
                'title_color' => 'text-[#231F1E]',
                'sub_color' => 'text-wedding-olive',
                'badge_style' => 'bg-wedding-dark text-wedding-gold border border-wedding-gold/40',
                'accent_color' => 'text-wedding-gold',
                'tag' => 'Wedding Classic Edition',
                'decal_icon' => '✨',
            ],
        };
    }
}

