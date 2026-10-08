<?php

namespace App\Http\Resources;

use App\Models\DownloadedMedia;
use Carbon\CarbonInterface;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\URL;

/** @property DownloadedMedia $resource */
class MediaResource extends JsonResource
{
    public function __construct($resource, private readonly CarbonInterface $expiresAt)
    {
        parent::__construct($resource);
    }

    public function toArray($request): array
    {
        $m = $this->resource;

        return [
            'id' => $m->id,
            'type' => $m->media_type,
            'file_name' => $m->file_name,
            'extension' => $m->file_extension,
            'mime_type' => $m->mime_type,
            'file_size' => $m->file_size,
            'width' => $m->width,
            'height' => $m->height,
            'duration' => $m->duration,
            'thumbnail' => $m->thumbnail_url,
            'sort_order' => $m->sort_order,
            // Time-limited signed URLs; the storage path is never exposed.
            'preview_url' => URL::temporarySignedRoute('media.preview', $this->expiresAt, ['id' => $m->id]),
            'download_url' => URL::temporarySignedRoute('media.download', $this->expiresAt, ['id' => $m->id]),
        ];
    }
}
