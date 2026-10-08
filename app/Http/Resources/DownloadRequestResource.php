<?php

namespace App\Http\Resources;

use App\Models\DownloadRequest;
use Illuminate\Http\Resources\Json\JsonResource;

/** @property DownloadRequest $resource */
class DownloadRequestResource extends JsonResource
{
    public function toArray($request): array
    {
        $r = $this->resource;
        $expired = $r->isExpired();
        $live = $r->isCompleted() && ! $expired;
        $expires = $r->expires_at ?? now()->addMinutes((int) config('media.retention_minutes'));

        $items = [];
        $archive = null;
        if ($live) {
            $r->loadMissing('media');
            foreach ($r->media as $m) {
                $res = (new MediaResource($m, $expires))->resolve();
                if ($m->media_type === 'zip') {
                    $archive = $res;
                } else {
                    $items[] = $res;
                }
            }
        }

        return [
            'uuid' => $r->uuid,
            'status' => $expired ? DownloadRequest::STATUS_EXPIRED : $r->status,
            'media_type' => $r->media_type,
            'title' => $r->title,
            'thumbnail' => $r->thumbnail_url,
            'expires_at' => $r->expires_at?->toIso8601String(),
            'error' => $r->isFailed() ? ['code' => $r->error_code, 'message' => $r->error_message] : null,
            'items' => $items,
            'archive' => $archive,
            'links' => [
                'status' => route('api.download.status', $r->uuid),
                'result' => route('api.download.result', $r->uuid),
            ],
        ];
    }
}
