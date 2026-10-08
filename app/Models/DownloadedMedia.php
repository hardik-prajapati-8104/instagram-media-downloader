<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DownloadedMedia extends Model
{
    protected $table = 'downloaded_media';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['duration' => 'float', 'file_size' => 'integer', 'width' => 'integer', 'height' => 'integer'];
    }

    public function downloadRequest(): BelongsTo
    {
        return $this->belongsTo(DownloadRequest::class, 'download_request_id');
    }

    public function isImage(): bool { return $this->media_type === 'image'; }
    public function isVideo(): bool { return $this->media_type === 'video'; }
    public function isAudio(): bool { return $this->media_type === 'audio'; }
    public function isArchive(): bool { return $this->media_type === 'zip'; }
}
