<?php

namespace App\Services\Instagram\DTO;

final class ProviderResponse
{
    public function __construct(
        public readonly int $status,
        public readonly array $body,
        public readonly int $durationMs,
    ) {
    }
}
