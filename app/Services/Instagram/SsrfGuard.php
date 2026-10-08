<?php

namespace App\Services\Instagram;

use App\Exceptions\MediaException;

/**
 * Every outbound request (provider API + media files) goes through here:
 * https only, no credentials, no IP literals, host allow-list, DNS must resolve to public IPs only,
 * and the validated IP is pinned for the actual connection (prevents DNS rebinding).
 */
class SsrfGuard
{
    /** @return array{host:string, ip:string} */
    public function assertProviderUrl(string $url): array
    {
        $host = strtolower(rtrim((string) parse_url($url, PHP_URL_HOST), '.'));

        return $this->check($url, [$host]);
    }

    /** @return array{host:string, ip:string} */
    public function assertMediaUrl(string $url, array $extraHosts = []): array
    {
        return $this->check($url, array_merge(config('media.allowed_media_hosts', []), $extraHosts));
    }

    /** Cheap, DNS-free check used before storing thumbnail URLs for display. */
    public function isAllowedMediaHostUrl(?string $url, array $extraHosts = []): bool
    {
        if (! $url) {
            return false;
        }
        $p = parse_url($url);
        if (! is_array($p) || strtolower($p['scheme'] ?? '') !== 'https' || empty($p['host']) || isset($p['user'])) {
            return false;
        }
        $host = strtolower(rtrim($p['host'], '.'));

        return ! filter_var($host, FILTER_VALIDATE_IP)
            && $this->hostAllowed($host, array_merge(config('media.allowed_media_hosts', []), $extraHosts));
    }

    public function pinnedCurlOptions(array $target): array
    {
        $ip = str_contains($target['ip'], ':') ? '[' . $target['ip'] . ']' : $target['ip'];

        return [CURLOPT_RESOLVE => ["{$target['host']}:443:{$ip}"]];
    }

    public static function isPublicIp(string $ip): bool
    {
        if (! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return false;
        }
        // CGNAT 100.64.0.0/10 is not covered by the PHP flags.
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $long = ip2long($ip);
            if ($long !== false && ($long & 0xFFC00000) === 0x64400000) {
                return false;
            }
        }

        return true;
    }

    /** @return list<string> */
    public function resolve(string $host): array
    {
        $ips = @gethostbynamel($host) ?: [];
        $v6 = @dns_get_record($host, DNS_AAAA) ?: [];
        foreach ($v6 as $r) {
            if (! empty($r['ipv6'])) {
                $ips[] = $r['ipv6'];
            }
        }

        return array_values(array_unique($ips));
    }

    private function check(string $url, array $allowedHosts): array
    {
        $p = parse_url($url);
        if (! is_array($p) || strtolower($p['scheme'] ?? '') !== 'https' || empty($p['host'])
            || isset($p['user']) || isset($p['pass']) || (isset($p['port']) && (int) $p['port'] !== 443)) {
            throw new MediaException('unsafe_media_url', 'Blocked: scheme/credentials/port.');
        }

        $host = strtolower(rtrim($p['host'], '.'));
        if (filter_var($host, FILTER_VALIDATE_IP) || str_starts_with($host, '[') || ! str_contains($host, '.')
            || in_array($host, ['localhost'], true) || str_ends_with($host, '.local') || str_ends_with($host, '.internal')) {
            throw new MediaException('unsafe_media_url', 'Blocked: IP literal or internal host: ' . $host);
        }

        if (! $this->hostAllowed($host, $allowedHosts)) {
            throw new MediaException('unsafe_media_url', 'Blocked: host not in allow-list: ' . $host);
        }

        $ips = $this->resolve($host);
        if ($ips === []) {
            throw new MediaException('download_failed', 'DNS resolution failed for ' . $host, true);
        }
        foreach ($ips as $ip) {
            if (! self::isPublicIp($ip)) {
                throw new MediaException('unsafe_media_url', "Blocked: {$host} resolves to non-public address {$ip}.");
            }
        }

        return ['host' => $host, 'ip' => $ips[0]];
    }

    private function hostAllowed(string $host, array $allowed): bool
    {
        foreach ($allowed as $a) {
            $a = strtolower(trim((string) $a));
            if ($a !== '' && ($host === $a || str_ends_with($host, '.' . $a))) {
                return true;
            }
        }

        return false;
    }
}
