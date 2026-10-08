<?php

namespace App\Services\Instagram;

use App\Exceptions\MediaException;
use App\Services\Instagram\DTO\MediaItem;
use App\Services\Instagram\DTO\MediaResult;
use App\Services\Instagram\DTO\ProviderResponse;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Psr\Http\Message\ResponseInterface;
use Throwable;

/**
 * Generic HTTP adapter for an AUTHORIZED media provider/API.
 * Sends the normalized Instagram URL, validates the reply, normalizes it. Swap providers by adding another adapter.
 */
final class InstagramProviderAdapter implements MediaProviderInterface
{
    public function __construct(
        private readonly SettingsService $settings,
        private readonly SsrfGuard $guard,
        private readonly ProviderResponseNormalizer $normalizer,
    ) {
    }

    public function name(): string
    {
        return 'http';
    }

    public function isConfigured(): bool
    {
        return (bool) ($this->settings->providerUrl() && $this->settings->providerKey());
    }

    public function supports(string $normalizedUrl): bool
    {
        return str_starts_with($normalizedUrl, 'https://www.instagram.com/');
    }

    public function fetchMedia(string $normalizedUrl): MediaResult
    {
        if (! $this->isConfigured()) {
            throw new MediaException('provider_not_configured', 'Provider URL or key missing.');
        }

        $endpoint = (string) $this->settings->providerUrl();
        $target = $this->guard->assertProviderUrl($endpoint);

        $header = (string) config('instagram.provider_auth_header');
        $scheme = trim((string) config('instagram.provider_auth_scheme'));
        $key = (string) $this->settings->providerKey();

        $http = Http::acceptJson()
            ->timeout(max(5, $this->settings->providerTimeout()))
            ->connectTimeout(10)
            ->withHeaders([$header => $scheme !== '' ? "{$scheme} {$key}" : $key])
            ->withOptions(['allow_redirects' => false, 'curl' => $this->guard->pinnedCurlOptions($target)]);

        $param = (string) config('instagram.provider_url_param', 'url');
        $started = microtime(true);

        try {
            $response = config('instagram.provider_method') === 'POST'
                ? $http->asJson()->post($endpoint, [$param => $normalizedUrl])
                : $http->get($endpoint, [$param => $normalizedUrl]);
        } catch (ConnectionException $e) {
            throw new MediaException('provider_timeout', 'Provider connection/timeout: ' . $e->getMessage(), true, $e);
        } catch (Throwable $e) {
            throw new MediaException('provider_unavailable', 'Provider call failed: ' . $e->getMessage(), true, $e);
        }

        $status = $response->status();
        $ms = (int) round((microtime(true) - $started) * 1000);

        match (true) {
            $status === 429 => throw new MediaException('provider_rate_limited', 'Provider HTTP 429.', true),
            $status === 401 => throw new MediaException('provider_unavailable', 'Provider HTTP 401 (check API key).'),
            $status === 403 => throw new MediaException('private_content', 'Provider HTTP 403.'),
            in_array($status, [404, 410], true) => throw new MediaException('deleted_content', "Provider HTTP {$status}."),
            $status >= 500 => throw new MediaException('provider_unavailable', "Provider HTTP {$status}.", true),
            $status >= 300 => throw new MediaException('provider_invalid_response', "Provider HTTP {$status} (redirects are not followed)."),
            default => null,
        };

        $json = $response->json();
        if (! is_array($json)) {
            throw new MediaException('provider_invalid_response', 'Provider body is not valid JSON.');
        }

        return $this->normalizer->normalize(new ProviderResponse($status, $json, $ms));
    }

    public function getMetadata(string $normalizedUrl): array
    {
        $r = $this->fetchMedia($normalizedUrl);

        return ['media_type_hint' => $r->mediaTypeHint, 'title' => $r->title, 'thumbnail' => $r->thumbnail, 'item_count' => count($r->items)];
    }

    public function downloadMedia(MediaItem $item, string $absoluteDestination, int $maxBytes): array
    {
        $providerHost = strtolower((string) parse_url((string) $this->settings->providerUrl(), PHP_URL_HOST));
        $url = $item->url;

        for ($hop = 0; $hop <= 3; $hop++) {
            $target = $this->guard->assertMediaUrl($url, [$providerHost]);

            try {
                $response = Http::withOptions([
                    'sink' => $absoluteDestination,
                    'allow_redirects' => false,
                    'curl' => $this->guard->pinnedCurlOptions($target),
                    'on_headers' => function (ResponseInterface $r) use ($maxBytes) {
                        $len = (int) $r->getHeaderLine('Content-Length');
                        if ($len > $maxBytes) {
                            throw new MediaException('file_too_large', "Content-Length {$len} exceeds limit.");
                        }
                    },
                    'progress' => function ($dlTotal, $dlNow) use ($maxBytes) {
                        if ($dlNow > $maxBytes) {
                            throw new MediaException('file_too_large', 'Download exceeded size limit.');
                        }
                    },
                ])->timeout(120)->connectTimeout(10)->get($url);
            } catch (Throwable $e) {
                @unlink($absoluteDestination);
                $inner = $e instanceof MediaException ? $e : ($e->getPrevious() instanceof MediaException ? $e->getPrevious() : null);
                throw $inner ?? new MediaException('download_failed', 'Media download failed: ' . $e->getMessage(), true, $e);
            }

            if (in_array($response->status(), [301, 302, 303, 307, 308], true)) {
                $location = $response->header('Location');
                if (! $location) {
                    @unlink($absoluteDestination);
                    throw new MediaException('download_failed', 'Redirect without Location.');
                }
                $url = str_starts_with($location, '/') ? 'https://' . $target['host'] . $location : $location;

                continue; // next hop is re-validated by assertMediaUrl()
            }

            if (! $response->successful()) {
                @unlink($absoluteDestination);
                $code = in_array($response->status(), [403, 404, 410], true) ? 'expired_media' : 'download_failed';
                throw new MediaException($code, 'Media host HTTP ' . $response->status(), $code === 'download_failed' && $response->status() >= 500);
            }

            clearstatcache(true, $absoluteDestination);
            $size = is_file($absoluteDestination) ? (int) filesize($absoluteDestination) : 0;
            if ($size === 0) {
                @unlink($absoluteDestination);
                throw new MediaException('download_failed', 'Downloaded file is empty.', true);
            }
            if ($size > $maxBytes) {
                @unlink($absoluteDestination);
                throw new MediaException('file_too_large', "File {$size} bytes exceeds limit.");
            }

            $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($absoluteDestination) ?: '';
            if (! array_key_exists($mime, config('media.allowed_mimes'))) {
                @unlink($absoluteDestination);
                throw new MediaException('invalid_file', "Disallowed MIME type: {$mime}");
            }

            return ['size' => $size, 'mime' => $mime];
        }

        @unlink($absoluteDestination);
        throw new MediaException('download_failed', 'Too many redirects.');
    }
}
