<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class DownloadRequest extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_EXPIRED = 'expired';

    public const STATUSES = [self::STATUS_PENDING, self::STATUS_PROCESSING, self::STATUS_COMPLETED, self::STATUS_FAILED, self::STATUS_EXPIRED];
    public const MEDIA_TYPES = ['video', 'reel', 'photo', 'carousel', 'audio'];

    protected $guarded = ['id'];

    protected $hidden = ['ip_hash', 'user_agent'];

    protected function casts(): array
    {
        return [
            'requested_at' => 'datetime',
            'completed_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $m) {
            $m->uuid ??= (string) Str::uuid();
            $m->requested_at ??= now();
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function media(): HasMany
    {
        return $this->hasMany(DownloadedMedia::class)->orderBy('sort_order');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(MediaProcessingLog::class);
    }

    // ---- scopes ----
    public function scopeStatus(Builder $q, string $status): Builder
    {
        return $q->where('status', $status);
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->whereIn('status', [self::STATUS_PENDING, self::STATUS_PROCESSING]);
    }

    public function scopeDueForExpiry(Builder $q): Builder
    {
        return $q->where('status', self::STATUS_COMPLETED)->where('expires_at', '<=', now());
    }

    public function scopeStale(Builder $q, int $minutes): Builder
    {
        return $q->active()->where('requested_at', '<', now()->subMinutes($minutes));
    }

    // ---- status helpers ----
    public function isPending(): bool { return $this->status === self::STATUS_PENDING; }
    public function isProcessing(): bool { return $this->status === self::STATUS_PROCESSING; }
    public function isCompleted(): bool { return $this->status === self::STATUS_COMPLETED; }
    public function isFailed(): bool { return $this->status === self::STATUS_FAILED; }

    public function isExpired(): bool
    {
        return $this->status === self::STATUS_EXPIRED
            || ($this->expires_at !== null && $this->expires_at->isPast() && $this->status === self::STATUS_COMPLETED);
    }
}
