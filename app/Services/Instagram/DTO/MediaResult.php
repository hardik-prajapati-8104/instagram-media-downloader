<?php

namespace App\Services\Instagram\DTO;

/** Internal, provider-independent result. Nothing outside the adapter depends on the raw provider format. */
final class MediaResult
{
    /** @param list<MediaItem> $items */
    public function __construct(
        public readonly ?string $mediaTypeHint,
        public readonly ?string $title,
        public readonly ?string $thumbnail,
        public readonly array $items,
    ) {
    }

    public function toArray(): array
    {
        return [
            'mediaTypeHint' => $this->mediaTypeHint,
            'title' => $this->title,
            'thumbnail' => $this->thumbnail,
            'items' => array_map(fn (MediaItem $i) => $i->toArray(), $this->items),
        ];
    }

    public static function fromArray(array $d): self
    {
        return new self($d['mediaTypeHint'] ?? null, $d['title'] ?? null, $d['thumbnail'] ?? null,
            array_map(fn ($i) => MediaItem::fromArray($i), $d['items'] ?? []));
    }
}
