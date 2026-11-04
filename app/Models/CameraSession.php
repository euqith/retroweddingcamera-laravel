<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CameraSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'device_token',
        'guest_name',
        'shots_taken',
        'max_shots',
        'reset_count',
        'ip_address',
        'user_agent',
        'last_shot_at',
        'last_reset_at',
    ];

    protected $casts = [
        'shots_taken' => 'integer',
        'max_shots' => 'integer',
        'reset_count' => 'integer',
        'last_shot_at' => 'datetime',
        'last_reset_at' => 'datetime',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function photos(): HasMany
    {
        return $this->hasMany(Photo::class)->latest('taken_at');
    }

    public function getRemainingShotsAttribute(): int
    {
        return max(0, $this->max_shots - $this->shots_taken);
    }

    public function isRollEmpty(): bool
    {
        return $this->shots_taken >= $this->max_shots;
    }

    public function resetRoll(bool $clearPhotos = false): void
    {
        $this->shots_taken = 0;
        $this->reset_count += 1;
        $this->last_reset_at = now();
        $this->save();

        if ($clearPhotos) {
            $this->photos()->delete();
        }
    }
}

