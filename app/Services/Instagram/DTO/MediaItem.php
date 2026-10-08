<?php

namespace App\Services\Instagram\DTO;

final class MediaItem
{
    public function __construct(
        public readonly string $type, // image | video | audio
        public readonly string $url,
        public readonly ?string $mimeType = null,
        public readonly ?string $extension = null,
        public readonly ?int $width = null,
        public readonly ?int $height = null,
        public readonly ?float $duration = null,
        public readonly ?string $quality = null,
        public readonly ?string $thumbnail = null,
        public readonly ?int $fileSize = null,
    ) {
    }

    public function toArray(): array
    {
        return get_object_vars($this);
    }

    public static function fromArray(array $d): self
    {
        return new self($d['type'], $d['url'], $d['mimeType'] ?? null, $d['extension'] ?? null, $d['width'] ?? null,
            $d['height'] ?? null, $d['duration'] ?? null, $d['quality'] ?? null, $d['thumbnail'] ?? null, $d['fileSize'] ?? null);
    }
}
