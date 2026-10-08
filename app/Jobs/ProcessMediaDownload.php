<?php

namespace App\Jobs;

use App\Exceptions\MediaException;
use App\Models\DownloadRequest;
use App\Services\Instagram\InstagramMediaService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class ProcessMediaDownload implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 300;
    public bool $failOnTimeout = true;

    public function __construct(public int $downloadRequestId)
    {
    }

    /** @return list<int> */
    public function backoff(): array
    {
        return [10, 30, 60];
    }

    public function handle(InstagramMediaService $service): void
    {
        $req = DownloadRequest::find($this->downloadRequestId);
        if (! $req || ! in_array($req->status, [DownloadRequest::STATUS_PENDING, DownloadRequest::STATUS_PROCESSING], true)) {
            return;
        }

        $final = app()->environment('local') || $this->attempts() >= $this->tries || $this->job?->getConnectionName() === 'sync';
        $service->process($req, $final);
    }

    /** Called after the last attempt (timeouts, worker crashes, unexpected exceptions): never leave a request "processing". */
    public function failed(Throwable $e): void
    {
        $req = DownloadRequest::find($this->downloadRequestId);
        if ($req && ! $req->isFailed() && ! $req->isCompleted()) {
            app(InstagramMediaService::class)->fail($req, [], $e instanceof MediaException ? $e : new MediaException('processing_failed', $e::class . ': ' . $e->getMessage()));
        }
    }
}
