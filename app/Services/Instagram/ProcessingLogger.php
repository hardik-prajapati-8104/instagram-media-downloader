<?php

namespace App\Services\Instagram;

use App\Models\DownloadRequest;
use App\Models\MediaProcessingLog;
use Illuminate\Support\Facades\Log;
use Throwable;

/** Structured logging to the log channel AND media_processing_logs, with secrets scrubbed. */
final class ProcessingLogger
{
    private const REDACT = ['api_key', 'apikey', 'authorization', 'cookie', 'token', 'password', 'secret', 'signature', 'x-api-key', 'key'];

    public function log(?DownloadRequest $request, string $level, string $event, string $message, array $context = []): void
    {
        $safe = $this->scrub($context);

        Log::log($level, $message, ['uuid' => $request?->uuid, 'event' => $event, 'provider' => $request?->provider] + $safe);

        if ($request) {
            try {
                MediaProcessingLog::create([
                    'download_request_id' => $request->id,
                    'level' => $level,
                    'event' => $event,
                    'message' => mb_substr($message, 0, 1000),
                    'context' => $safe,
                    'created_at' => now(),
                ]);
            } catch (Throwable $e) {
                Log::warning('Could not persist processing log: ' . $e->getMessage());
            }
        }
    }

    public function scrub(array $data): array
    {
        $out = [];
        foreach ($data as $k => $v) {
            if (is_string($k) && in_array(strtolower($k), self::REDACT, true)) {
                $out[$k] = '[redacted]';
            } elseif (is_array($v)) {
                $out[$k] = $this->scrub($v);
            } elseif (is_string($v)) {
                $out[$k] = preg_replace('/\?[^\s"\']*/', '?…', mb_substr($v, 0, 1000));
            } else {
                $out[$k] = $v;
            }
        }

        return $out;
    }
}
