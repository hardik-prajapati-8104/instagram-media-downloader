<?php

namespace App\Services\Instagram;

use App\Services\Instagram\DTO\MediaItem;
use App\Services\Instagram\DTO\MediaResult;

interface MediaProviderInterface
{
    public function name(): string;

    /** True when endpoint/credentials are present. */
    public function isConfigured(): bool;

    public function supports(string $normalizedUrl): bool;

    /** Call the provider and return the normalized internal result. */
    public function fetchMedia(string $normalizedUrl): MediaResult;

    /** Light summary (type hint, title, thumbnail, item count). */
    public function getMetadata(string $normalizedUrl): array;

    /**
     * Stream one item to $absoluteDestination (SSRF-guarded, size-capped).
     *
     * @return array{size:int, mime:string}
     */
    public function downloadMedia(MediaItem $item, string $absoluteDestination, int $maxBytes): array;
}
