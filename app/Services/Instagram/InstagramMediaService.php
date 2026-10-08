<?php

namespace App\Services\Instagram;

use App\Exceptions\MediaException;
use App\Jobs\ProcessMediaDownload;
use App\Models\DownloadRequest;
use App\Services\Instagram\DTO\MediaResult;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Throwable;

/** Orchestrates: validate -> dedupe -> queue -> provider -> detect -> store -> complete/fail. */
final class InstagramMediaService
{
    public function __construct(
        private readonly InstagramUrlValidator $validator,
        private readonly MediaProviderManager $providers,
        private readonly MediaTypeDetectionService $detector,
        private readonly MediaProcessingService $processing,
        private readonly ProcessingLogger $logger,
        private readonly StatisticsService $stats,
        private readonly SettingsService $settings,
    ) {
    }

    /**
     * @return array{0:DownloadRequest,1:bool} [request, reused]
     *
     * @throws MediaException
     */
    public function submit(string $url, string $type, ?string $ipHash, ?string $userAgent): array
    {
        $normalized = $this->validator->normalize($url);
        $this->providers->resolve(); // fail fast: never accept work we cannot do

        $mode = $type === 'audio' ? 'audio' : 'media';

        try {
            return Cache::lock('ig:submit:' . sha1($normalized . '|' . $mode), 15)->block(5, function () use ($url, $normalized, $mode, $ipHash, $userAgent) {
                if ($existing = $this->findReusable($normalized, $mode)) {
                    $this->logger->log($existing, 'info', 'request.reused', 'Existing request reused');

                    return [$existing, true];
                }

                $req = DownloadRequest::create([
                    'original_url' => mb_substr(strtok($url, '?#') ?: $url, 0, 2048),
                    'normalized_url' => $normalized,
                    'requested_mode' => $mode,
                    'status' => DownloadRequest::STATUS_PENDING,
                    'ip_hash' => $ipHash,
                    'user_agent' => $userAgent ? mb_substr($userAgent, 0, 250) : null,
                ]);
                $this->logger->log($req, 'info', 'request.created', 'Download request created', ['mode' => $mode]);

                if (app()->environment('local')) {
                    ProcessMediaDownload::dispatchSync($req->id);
                } else {
                    ProcessMediaDownload::dispatch($req->id)->onQueue($this->settings->queueName());
                }

                return [$req->fresh(), false];
            });
        } catch (LockTimeoutException) {
            throw new MediaException('rate_limit', 'Could not obtain submit lock.');
        }
    }

    public function retry(DownloadRequest $req): DownloadRequest
    {
        $this->providers->resolve();
        $req->update([
            'status' => DownloadRequest::STATUS_PENDING, 'error_code' => null, 'error_message' => null,
            'completed_at' => null, 'expires_at' => null, 'requested_at' => now(),
        ]);
        $this->logger->log($req, 'info', 'request.retry', 'Request re-queued');
        if (app()->environment('local')) {
            ProcessMediaDownload::dispatchSync($req->id);
        } else {
            ProcessMediaDownload::dispatch($req->id)->onQueue($this->settings->queueName());
        }

        return $req->fresh();
    }

    public function process(DownloadRequest $req, bool $isFinalAttempt = true): void
    {
        $started = microtime(true);
        $req->update(['status' => DownloadRequest::STATUS_PROCESSING, 'error_code' => null, 'error_message' => null]);

        try {
            $provider = $this->providers->resolve();
            $req->update(['provider' => $provider->name()]);

            $result = $this->fetch($provider, $req->normalized_url);
            $type = $this->detector->detect($result, $req->normalized_url, $req->requested_mode);

            $this->processing->store($req, $result, $type, $provider);

            $req->update([
                'status' => DownloadRequest::STATUS_COMPLETED,
                'media_type' => $type,
                'title' => $result->title,
                'thumbnail_url' => $req->media()->whereNotNull('thumbnail_url')->value('thumbnail_url'),
                'completed_at' => now(),
                'expires_at' => now()->addMinutes($this->settings->retentionMinutes()),
            ]);

            $this->stats->record($type, true);
            $this->logger->log($req, 'info', 'request.completed', 'Request completed', [
                'duration_ms' => (int) round((microtime(true) - $started) * 1000), 'media_type' => $type, 'items' => count($result->items),
            ]);
        } catch (Throwable $e) {
            $ex = $e instanceof MediaException ? $e : new MediaException('processing_failed', $e::class . ': ' . $e->getMessage(), false, $e);

            if ($ex->retryable && ! $isFinalAttempt) {
                $this->logger->log($req, 'warning', 'request.retrying', 'Retryable failure', ['code' => $ex->errorCode, 'detail' => $ex->getMessage()]);
                throw $ex;
            }

            $this->fail($req, ['duration_ms' => (int) round((microtime(true) - $started) * 1000)], $ex);
        }
    }

    public function fail(DownloadRequest $req, array $context, MediaException $e): void
    {
        $req->update([
            'status' => DownloadRequest::STATUS_FAILED,
            'error_code' => $e->errorCode,
            'error_message' => $e->userMessage(),
            'completed_at' => now(),
        ]);

        $this->stats->record($req->media_type ?? ($req->requested_mode === 'audio' ? 'audio' : 'unknown'), false);
        $this->logger->log($req, 'error', 'request.failed', 'Request failed', $context + ['code' => $e->errorCode, 'detail' => $e->getMessage()]);
    }

    /** Marks a completed-but-past-expiry request as expired and removes its files (lazy expiry). */
    public function expireIfDue(DownloadRequest $req): DownloadRequest
    {
        if ($req->isCompleted() && $req->expires_at && $req->expires_at->isPast()) {
            $this->processing->purge($req);
            $req->update(['status' => DownloadRequest::STATUS_EXPIRED]);
        }

        return $req;
    }

    private function fetch(MediaProviderInterface $provider, string $url): MediaResult
    {
        $data = Cache::remember('ig:meta:' . sha1($url), (int) config('instagram.cache_ttl', 600), fn () => $provider->fetchMedia($url)->toArray());

        return MediaResult::fromArray($data);
    }

    private function findReusable(string $normalized, string $mode): ?DownloadRequest
    {
        $base = DownloadRequest::where('normalized_url', $normalized)->where('requested_mode', $mode);

        $active = (clone $base)->active()
            ->where('requested_at', '>=', now()->subMinutes((int) config('media.stale_minutes', 30)))
            ->latest('id')->first();
        if ($active) {
            return $active;
        }

        $done = (clone $base)->where('status', DownloadRequest::STATUS_COMPLETED)->where('expires_at', '>', now())->latest('id')->first();
        if ($done && $this->filesExist($done)) {
            return $done;
        }

        return null;
    }

    private function filesExist(DownloadRequest $req): bool
    {
        $media = $req->media()->get();
        if ($media->isEmpty()) {
            return false;
        }
        $disk = Storage::disk(config('media.disk'));

        return $media->every(fn ($m) => $disk->exists($m->storage_path));
    }
}
