<?php

namespace App\Services\Instagram;

use App\Exceptions\MediaException;

final class MediaProviderManager
{
    public function __construct(private readonly SettingsService $settings)
    {
    }

    /** @throws MediaException provider_not_configured */
    public function resolve(): MediaProviderInterface
    {
        $name = $this->settings->providerName();
        $class = $name ? config("instagram.providers.{$name}") : null;

        if (! $class) {
            throw new MediaException('provider_not_configured', 'No provider selected or unknown provider.');
        }

        $provider = app($class);
        if (! $provider instanceof MediaProviderInterface || ! $provider->isConfigured()) {
            throw new MediaException('provider_not_configured', 'Provider selected but endpoint/key missing.');
        }

        return $provider;
    }

    public function isReady(): bool
    {
        try {
            $this->resolve();

            return true;
        } catch (MediaException) {
            return false;
        }
    }
}
