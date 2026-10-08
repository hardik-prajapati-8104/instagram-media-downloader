<?php

namespace App\Http\Controllers;

use App\Exceptions\MediaException;
use App\Http\Requests\StoreDownloadRequest;
use App\Http\Resources\DownloadRequestResource;
use App\Models\DownloadRequest;
use App\Services\Instagram\InstagramMediaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DownloadController extends Controller
{
    public function __construct(private readonly InstagramMediaService $service)
    {
    }

    public function store(StoreDownloadRequest $request): JsonResponse
    {
        try {
            [$dl] = $this->service->submit(
                (string) $request->input('url'),
                (string) $request->input('type', 'media'),
                $this->ipHash($request),
                $request->userAgent(),
            );
        } catch (MediaException $e) {
            return $this->error($e);
        }

        $dl = $this->service->expireIfDue($dl);

        if ($dl->isFailed()) { // sync queue (or very fast job): report the failure immediately instead of "accepted"
            $ex = new MediaException($dl->error_code ?? 'processing_failed');

            return response()->json(['success' => false, 'message' => $dl->error_message ?? $ex->userMessage(), 'error' => ['code' => $ex->errorCode], 'data' => (new DownloadRequestResource($dl))->resolve()], $ex->statusCode());
        }

        return $this->ok($dl, $dl->isCompleted() ? 'Media processed successfully.' : 'Your request is being processed.', $dl->isCompleted() ? 200 : 202);
    }

    public function status(string $uuid): JsonResponse
    {
        $dl = $this->service->expireIfDue(DownloadRequest::where('uuid', $uuid)->firstOrFail());
        $data = (new DownloadRequestResource($dl))->resolve();
        unset($data['items'], $data['archive']); // status polling never returns file links

        return response()->json(['success' => true, 'message' => 'Status retrieved.', 'data' => $data]);
    }

    public function result(string $uuid): JsonResponse
    {
        $dl = $this->service->expireIfDue(DownloadRequest::where('uuid', $uuid)->firstOrFail());

        if ($dl->isExpired()) {
            return $this->error(new MediaException('expired_result'));
        }
        if ($dl->isFailed()) {
            return $this->error(new MediaException($dl->error_code ?? 'processing_failed'));
        }
        if (! $dl->isCompleted()) {
            return $this->ok($dl, 'Your request is still being processed.', 409);
        }

        return $this->ok($dl, 'Media processed successfully.');
    }

    public function retry(string $uuid): JsonResponse
    {
        $dl = DownloadRequest::where('uuid', $uuid)->firstOrFail();

        if (! ($dl->isFailed() || $dl->isExpired() || $dl->status === DownloadRequest::STATUS_EXPIRED)) {
            return response()->json(['success' => false, 'message' => 'Only failed or expired requests can be retried.', 'error' => ['code' => 'not_retryable']], 409);
        }

        try {
            $dl = $this->service->retry($dl);
        } catch (MediaException $e) {
            return $this->error($e);
        }

        return $this->ok($dl, 'Your request is being processed.', 202);
    }

    private function ok(DownloadRequest $dl, string $message, int $status = 200): JsonResponse
    {
        return response()->json(['success' => true, 'message' => $message, 'data' => (new DownloadRequestResource($dl))->resolve()], $status);
    }

    private function error(MediaException $e): JsonResponse
    {
        return response()->json(['success' => false, 'message' => $e->userMessage(), 'error' => ['code' => $e->errorCode]], $e->statusCode());
    }

    private function ipHash(Request $request): string
    {
        return hash_hmac('sha256', (string) $request->ip(), (string) config('app.key'));
    }
}
