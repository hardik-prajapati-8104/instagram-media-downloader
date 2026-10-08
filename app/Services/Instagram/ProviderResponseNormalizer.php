<?php

namespace App\Services\Instagram;

use App\Exceptions\MediaException;
use App\Services\Instagram\DTO\MediaItem;
use App\Services\Instagram\DTO\MediaResult;
use App\Services\Instagram\DTO\ProviderResponse;
use Illuminate\Support\Str;

/** Maps a raw provider JSON body to MediaResult. Tolerant of common key names; strict about URLs. */
final class ProviderResponseNormalizer
{
    private const URL_KEYS = ['url', 'download_url', 'src', 'video_url', 'image_url', 'media_url'];
    private const LIST_KEYS = ['items', 'medias', 'media', 'results', 'carousel', 'links'];

    public function normalize(ProviderResponse $response): MediaResult
    {
        $raw = $response->body;
        if ($raw === []) {
            throw new MediaException('empty_response', 'Provider returned an empty body.', true);
        }

        $this->assertNoProviderError($raw);

        $payload = (isset($raw['data']) && is_array($raw['data'])) ? $raw['data'] : $raw;

        $list = null;
        foreach (self::LIST_KEYS as $k) {
            if (isset($payload[$k]) && is_array($payload[$k])) {
                $list = $payload[$k];
                break;
            }
        }
        if ($list === null && $this->firstString($payload, self::URL_KEYS)) {
            $list = [$payload];
        }
        if ($list === null) {
            throw new MediaException('provider_invalid_response', 'No media list in provider response.');
        }
        if (! array_is_list($list)) {
            $list = [$list];
        }

        $items = [];
        foreach ($list as $row) {
            if (is_array($row) && ($item = $this->item($row))) {
                $items[] = $item;
            }
        }
        if ($items === []) {
            throw new MediaException('missing_media', 'Provider response contained no usable https media URL.');
        }

        $title = $this->firstString($payload, ['title', 'caption', 'description']);
        $thumb = $this->firstString($payload, ['thumbnail', 'thumbnail_url', 'thumb', 'cover']);
        $hint = $this->firstString($payload, ['media_type', 'type']);

        return new MediaResult(
            $hint ? strtolower($hint) : null,
            $title ? (Str::limit(trim(strip_tags($title)), 200, '') ?: null) : null,
            ($thumb && str_starts_with(strtolower($thumb), 'https://')) ? $thumb : null,
            $items,
        );
    }

    private function item(array $row): ?MediaItem
    {
        $url = $this->firstString($row, self::URL_KEYS);
        if (! $url || ! str_starts_with(strtolower($url), 'https://') || strlen($url) > 4096) {
            return null;
        }

        $mime = strtolower((string) ($row['mime_type'] ?? $row['mimeType'] ?? $row['content_type'] ?? ''));
        $mime = preg_match('#^[a-z]+/[a-z0-9.+-]+$#', $mime) ? $mime : null;

        $type = $this->type($row['type'] ?? $row['media_type'] ?? $row['kind'] ?? null, $mime, $url);
        if (! $type) {
            return null;
        }

        $thumb = $this->firstString($row, ['thumbnail', 'thumbnail_url', 'thumb', 'cover']);

        return new MediaItem(
            $type, $url, $mime,
            $this->firstString($row, ['extension', 'ext']),
            $this->int($row['width'] ?? null),
            $this->int($row['height'] ?? null),
            isset($row['duration']) && is_numeric($row['duration']) ? (float) $row['duration'] : null,
            $this->firstString($row, ['quality', 'label']),
            ($thumb && str_starts_with(strtolower($thumb), 'https://')) ? $thumb : null,
            $this->int($row['file_size'] ?? $row['size'] ?? null),
        );
    }

    private function type(mixed $declared, ?string $mime, string $url): ?string
    {
        $d = is_string($declared) ? strtolower($declared) : '';
        if (in_array($d, ['video', 'reel', 'clip'], true)) return 'video';
        if (in_array($d, ['image', 'photo', 'picture'], true)) return 'image';
        if (in_array($d, ['audio', 'music'], true)) return 'audio';

        if ($mime) {
            foreach (['video', 'image', 'audio'] as $t) {
                if (str_starts_with($mime, $t . '/')) return $t;
            }
        }

        $ext = strtolower(pathinfo((string) parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION));

        return match (true) {
            in_array($ext, ['mp4', 'mov', 'webm', 'm4v'], true) => 'video',
            in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true) => 'image',
            in_array($ext, ['mp3', 'm4a'], true) => 'audio',
            default => null,
        };
    }

    private function assertNoProviderError(array $raw): void
    {
        $failed = (($raw['success'] ?? true) === false) || (($raw['status'] ?? '') === 'error') || ! empty($raw['error']);
        if (! $failed) {
            return;
        }

        $error = is_array($raw['error'] ?? null) ? $raw['error'] : [];
        $hay = strtolower(implode(' ', array_filter([
            is_string($raw['error'] ?? null) ? $raw['error'] : '',
            (string) ($error['code'] ?? ''),
            (string) ($error['message'] ?? ''),
            (string) ($raw['error_code'] ?? ''),
            (string) ($raw['code'] ?? ''),
            (string) ($raw['message'] ?? ''),
        ])));

        $code = match (true) {
            (bool) preg_match('/private|login|not_public|restricted|private_content/', $hay) => 'private_content',
            (bool) preg_match('/deleted|not[_ ]found|removed|media_not_found/', $hay) => 'deleted_content',
            (bool) preg_match('/rate|limit|quota|too many|rate_limited|quota_exceeded/', $hay) => 'provider_rate_limited',
            (bool) preg_match('/expired/', $hay) => 'expired_media',
            default => 'provider_unavailable',
        };

        throw new MediaException($code, 'Provider reported failure: ' . mb_substr($hay, 0, 200), $code === 'provider_rate_limited');
    }

    private function firstString(array $a, array $keys): ?string
    {
        foreach ($keys as $k) {
            if (isset($a[$k]) && is_string($a[$k]) && trim($a[$k]) !== '') {
                return trim($a[$k]);
            }
        }

        return null;
    }

    private function int(mixed $v): ?int
    {
        return is_numeric($v) && (int) $v > 0 ? (int) $v : null;
    }
}
