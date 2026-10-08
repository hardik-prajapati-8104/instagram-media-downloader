<?php

namespace App\Exceptions;

use RuntimeException;
use Throwable;

/**
 * Single domain exception. $errorCode maps to a user-safe message in config/instagram.php;
 * $getMessage() holds the INTERNAL detail (log only, never shown to users).
 */
class MediaException extends RuntimeException
{
    public function __construct(
        public readonly string $errorCode,
        string $internalMessage = '',
        public readonly bool $retryable = false,
        ?Throwable $previous = null,
    ) {
        parent::__construct($internalMessage !== '' ? $internalMessage : $errorCode, 0, $previous);
    }

    public function userMessage(): string
    {
        return config("instagram.errors.{$this->errorCode}") ?? config('instagram.errors.processing_failed');
    }

    public function statusCode(): int
    {
        return match ($this->errorCode) {
            'invalid_url', 'unsupported_url' => 422,
            'provider_not_configured' => 503,
            'provider_unavailable', 'provider_invalid_response' => 502,
            'provider_timeout' => 504,
            'provider_rate_limited', 'rate_limit' => 429,
            'private_content' => 403,
            'deleted_content' => 404,
            'expired_result', 'expired_media' => 410,
            default => 400,
        };
    }
}
