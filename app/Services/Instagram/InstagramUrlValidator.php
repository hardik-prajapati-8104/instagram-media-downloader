<?php

namespace App\Services\Instagram;

use App\Exceptions\MediaException;

final class InstagramUrlValidator
{
    private const PATH_PATTERN = '#^/(?:[A-Za-z0-9._]{1,30}/)?(p|reel|reels|tv)/([A-Za-z0-9_-]{5,40})/?$#';

    /**
     * @throws MediaException invalid_url | unsupported_url
     */
    public function normalize(string $input): string
    {
        $input = trim($input);

        if ($input === '' || strlen($input) > 2048 || preg_match('/[\x00-\x20\x7f<>"\'`\\\\]/', $input)) {
            throw new MediaException('invalid_url', 'Rejected: empty, too long or contains forbidden characters.');
        }

        $parts = parse_url($input);
        if ($parts === false || ! isset($parts['scheme'], $parts['host'])) {
            throw new MediaException('invalid_url', 'Rejected: malformed URL.');
        }

        if (strtolower($parts['scheme']) !== 'https') {
            throw new MediaException('invalid_url', 'Rejected: only https is accepted.');
        }

        if (isset($parts['user']) || isset($parts['pass']) || (isset($parts['port']) && (int) $parts['port'] !== 443)) {
            throw new MediaException('invalid_url', 'Rejected: credentials or custom port in URL.');
        }

        $host = strtolower(rtrim($parts['host'], '.'));
        if (! in_array($host, config('instagram.allowed_domains', []), true)) {
            throw new MediaException('unsupported_url', 'Rejected: host not allowed: ' . $host);
        }

        if (! preg_match(self::PATH_PATTERN, $parts['path'] ?? '', $m)) {
            throw new MediaException('unsupported_url', 'Rejected: path is not a post/reel/tv URL.');
        }

        $kind = $m[1] === 'reels' ? 'reel' : $m[1];

        // Query string and fragment (igsh, utm_*, img_index ...) are dropped: they never change which media is served.
        return "https://www.instagram.com/{$kind}/{$m[2]}/";
    }

    public function isValid(string $input): bool
    {
        try {
            $this->normalize($input);

            return true;
        } catch (MediaException) {
            return false;
        }
    }

    /** First path segment of a NORMALIZED url: p | reel | tv */
    public function kind(string $normalizedUrl): string
    {
        return explode('/', trim((string) parse_url($normalizedUrl, PHP_URL_PATH), '/'))[0] ?? '';
    }

    public function shortcode(string $normalizedUrl): string
    {
        $seg = explode('/', trim((string) parse_url($normalizedUrl, PHP_URL_PATH), '/'));

        return preg_replace('/[^A-Za-z0-9_-]/', '', $seg[1] ?? 'media') ?: 'media';
    }
}
