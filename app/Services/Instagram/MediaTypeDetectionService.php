<?php

namespace App\Services\Instagram;

use App\Exceptions\MediaException;
use App\Services\Instagram\DTO\MediaResult;

/** Media type comes from what the provider actually returned. The URL only labels video vs reel. */
final class MediaTypeDetectionService
{
    public function __construct(private readonly InstagramUrlValidator $validator)
    {
    }

    /** @return string video|reel|photo|carousel|audio */
    public function detect(MediaResult $result, string $normalizedUrl, string $mode = 'media'): string
    {
        if ($mode === 'audio') {
            return 'audio';
        }

        $count = count($result->items);
        if ($count === 0) {
            throw new MediaException('missing_media', 'No items to detect from.');
        }
        if ($count > 1) {
            return 'carousel';
        }

        return match ($result->items[0]->type) {
            'image' => 'photo',
            'video' => $this->validator->kind($normalizedUrl) === 'reel' ? 'reel' : 'video',
            'audio' => 'audio',
            default => throw new MediaException('unsupported_media', 'Unknown item type.'),
        };
    }
}
