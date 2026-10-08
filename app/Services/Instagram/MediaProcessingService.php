<?php

namespace App\Services\Instagram;

use App\Exceptions\MediaException;
use App\Models\DownloadedMedia;
use App\Models\DownloadRequest;
use App\Services\Instagram\DTO\MediaItem;
use App\Services\Instagram\DTO\MediaResult;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use Throwable;
use ZipArchive;

/** Downloads provider media into private storage, validates it, records DB rows, builds ZIP / MP3. */
final class MediaProcessingService
{
    public function __construct(
        private readonly SettingsService $settings,
        private readonly SsrfGuard $guard,
        private readonly InstagramUrlValidator $validator,
        private readonly AudioExtractionService $audio,
    ) {
    }

    public function store(DownloadRequest $req, MediaResult $result, string $type, MediaProviderInterface $provider): void
    {
        $disk = $this->disk();
        $dir = $this->dir($req);

        $this->purge($req);
        $disk->makeDirectory($dir);

        try {
            $type === 'audio'
                ? $this->storeAudio($req, $result, $provider, $disk, $dir)
                : $this->storeItems($req, $result, $provider, $disk, $dir);
        } catch (Throwable $e) {
            $disk->deleteDirectory($dir);
            $disk->delete($this->base() . "/tmp/{$req->uuid}-source.tmp");
            DownloadedMedia::where('download_request_id', $req->id)->delete();
            throw $e;
        }
    }

    public function purge(DownloadRequest $req): void
    {
        $req->media()->delete();
        $this->disk()->deleteDirectory($this->dir($req));
    }

    public function dir(DownloadRequest $req): string
    {
        return $this->base() . '/' . $req->uuid;
    }

    public function base(): string
    {
        return trim((string) config('media.directory'), '/');
    }

    private function disk(): Filesystem
    {
        return Storage::disk(config('media.disk'));
    }

    private function storeItems(DownloadRequest $req, MediaResult $result, MediaProviderInterface $provider, Filesystem $disk, string $dir): void
    {
        $items = array_slice($result->items, 0, (int) config('media.max_carousel_items', 20));
        $code = $this->validator->shortcode($req->normalized_url);
        $rows = [];

        foreach ($items as $i => $item) {
            $n = $i + 1;
            $tmpRel = "{$dir}/part-{$n}.tmp";
            $meta = $provider->downloadMedia($item, $disk->path($tmpRel), $this->settings->maxFileSize());

            $expectedPrefix = $item->type === 'image' ? 'image/' : ($item->type === 'video' ? 'video/' : 'audio/');
            if (! str_starts_with($meta['mime'], $expectedPrefix)) {
                @unlink($disk->path($tmpRel));
                throw new MediaException('unsupported_media', "Item {$n} declared {$item->type} but file is {$meta['mime']}.");
            }

            $ext = config('media.allowed_mimes')[$meta['mime']];
            $finalRel = "{$dir}/{$n}.{$ext}";
            if (! @rename($disk->path($tmpRel), $disk->path($finalRel))) {
                throw new MediaException('processing_failed', 'Could not move downloaded file into place.');
            }

            [$w, $h, $dur] = $this->dimensions($disk->path($finalRel), $item);

            $rows[] = DownloadedMedia::create([
                'download_request_id' => $req->id,
                'media_type' => $item->type,
                'original_media_url' => strtok($item->url, '?') ?: null,
                'thumbnail_url' => $this->safeThumb($item->thumbnail ?? $result->thumbnail),
                'storage_path' => $finalRel,
                'file_name' => "instagram-{$code}-{$n}.{$ext}",
                'file_extension' => $ext,
                'mime_type' => $meta['mime'],
                'file_size' => $meta['size'],
                'duration' => $dur,
                'width' => $w,
                'height' => $h,
                'sort_order' => $n,
            ]);
        }

        if (count($rows) > 1) {
            $this->createZip($req, $rows, $disk, $dir, $code);
        }
    }

    private function storeAudio(DownloadRequest $req, MediaResult $result, MediaProviderInterface $provider, Filesystem $disk, string $dir): void
    {
        $source = null;
        foreach ($result->items as $item) {
            if ($item->type === 'video') { $source = $item; break; }
        }
        $source ??= collect($result->items)->first(fn (MediaItem $i) => $i->type === 'audio');
        if (! $source) {
            throw new MediaException('unsupported_media', 'No video/audio item available for audio extraction.');
        }

        $disk->makeDirectory($this->base() . '/tmp');
        $tmpRel = $this->base() . "/tmp/{$req->uuid}-source.tmp";
        $tmpAbs = $disk->path($tmpRel);
        $code = $this->validator->shortcode($req->normalized_url);

        try {
            $provider->downloadMedia($source, $tmpAbs, $this->settings->maxFileSize());
            $this->audio->assertHasAudio($tmpAbs);

            $outRel = "{$dir}/audio.mp3";
            $info = $this->audio->extractMp3($tmpAbs, $disk->path($outRel));
        } finally {
            @unlink($tmpAbs);
        }

        DownloadedMedia::create([
            'download_request_id' => $req->id,
            'media_type' => 'audio',
            'original_media_url' => strtok($source->url, '?') ?: null,
            'thumbnail_url' => $this->safeThumb($source->thumbnail ?? $result->thumbnail),
            'storage_path' => $outRel,
            'file_name' => "instagram-{$code}-audio.mp3",
            'file_extension' => 'mp3',
            'mime_type' => 'audio/mpeg',
            'file_size' => $info['size'],
            'duration' => $info['duration'],
            'sort_order' => 1,
        ]);
    }

    /** @param list<DownloadedMedia> $rows */
    private function createZip(DownloadRequest $req, array $rows, Filesystem $disk, string $dir, string $code): void
    {
        $rel = "{$dir}/carousel.zip";
        $zip = new ZipArchive();

        if ($zip->open($disk->path($rel), ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new MediaException('processing_failed', 'Could not open ZIP for writing.');
        }
        foreach ($rows as $row) {
            if (! is_file($disk->path($row->storage_path)) || ! $zip->addFile($disk->path($row->storage_path), $row->file_name)) {
                $zip->close();
                throw new MediaException('processing_failed', 'Could not add file to ZIP: ' . $row->file_name);
            }
        }
        if (! $zip->close() || ! is_file($disk->path($rel))) {
            throw new MediaException('processing_failed', 'ZIP creation failed.');
        }

        DownloadedMedia::create([
            'download_request_id' => $req->id,
            'media_type' => 'zip',
            'storage_path' => $rel,
            'file_name' => "instagram-{$code}-carousel.zip",
            'file_extension' => 'zip',
            'mime_type' => 'application/zip',
            'file_size' => (int) filesize($disk->path($rel)),
            'sort_order' => 9999,
        ]);
    }

    /** @return array{0:?int,1:?int,2:?float} */
    private function dimensions(string $abs, MediaItem $item): array
    {
        $w = $item->width;
        $h = $item->height;
        $d = $item->duration;

        if ($item->type === 'image' && ($size = @getimagesize($abs))) {
            [$w, $h] = [$size[0], $size[1]];
        } elseif ($item->type === 'video' && ($p = $this->audio->probe($abs))) {
            $w = $p['width'] ?? $w;
            $h = $p['height'] ?? $h;
            $d = $p['duration'] ?? $d;
        }

        return [$w, $h, $d];
    }

    private function safeThumb(?string $url): ?string
    {
        return $this->guard->isAllowedMediaHostUrl($url) ? $url : null;
    }
}
