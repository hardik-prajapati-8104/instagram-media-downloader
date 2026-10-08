<?php

namespace App\Services\Instagram;

use App\Models\AppSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Throwable;

/** Admin-editable settings (DB) with .env/config fallback. API keys are encrypted at rest. */
class SettingsService
{
    private const CACHE_KEY = 'app_settings.all';

    public function all(): array
    {
        try {
            return Cache::remember(self::CACHE_KEY, 300, function () {
                $out = [];
                foreach (AppSetting::all() as $s) {
                    try {
                        $out[$s->key] = ($s->is_encrypted && $s->value !== null) ? Crypt::decryptString($s->value) : $s->value;
                    } catch (Throwable) {
                        $out[$s->key] = null;
                    }
                }

                return $out;
            });
        } catch (Throwable) {
            return []; // table missing (before migrate) or DB down: fall back to config
        }
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $v = $this->all()[$key] ?? null;

        return ($v === null || $v === '') ? $default : $v;
    }

    public function set(string $key, ?string $value, bool $encrypted = false): void
    {
        AppSetting::updateOrCreate(['key' => $key], [
            'value' => ($encrypted && $value !== null && $value !== '') ? Crypt::encryptString($value) : $value,
            'is_encrypted' => $encrypted,
        ]);
        Cache::forget(self::CACHE_KEY);
    }

    public function providerName(): ?string { return $this->get('provider', config('instagram.provider')) ?: null; }
    public function providerUrl(): ?string { return $this->get('provider_url', config('instagram.provider_api_url')) ?: null; }
    public function providerKey(): ?string { return $this->get('provider_api_key', config('instagram.provider_api_key')) ?: null; }
    public function providerTimeout(): int { return (int) $this->get('provider_timeout', config('instagram.provider_timeout')); }
    public function rateLimit(): int { return max(1, (int) $this->get('rate_limit', config('media.rate.limit'))); }
    public function rateWindow(): int { return max(1, (int) $this->get('rate_window', config('media.rate.window'))); }
    public function retentionMinutes(): int { return max(1, (int) $this->get('retention_minutes', config('media.retention_minutes'))); }
    public function maxFileSize(): int { return max(1024, (int) $this->get('max_file_size', config('media.max_file_size'))); }
    public function queueName(): string { return (string) $this->get('queue_name', config('media.queue')); }
    public function ffmpeg(): string { return (string) $this->get('ffmpeg_binary', config('media.ffmpeg')); }
    public function ffprobe(): string { return (string) $this->get('ffprobe_binary', config('media.ffprobe')); }

    public function maskedProviderKey(): ?string
    {
        $k = $this->providerKey();

        return $k ? '••••••••' . substr($k, -4) : null;
    }
}
