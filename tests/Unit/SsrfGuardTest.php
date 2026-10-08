<?php

namespace Tests\Unit;

use App\Exceptions\MediaException;
use App\Services\Instagram\SsrfGuard;
use Mockery;
use Tests\TestCase;

class SsrfGuardTest extends TestCase
{
    private function guard(array $ips = ['93.184.216.34']): SsrfGuard
    {
        return Mockery::mock(SsrfGuard::class)->makePartial()->shouldReceive('resolve')->andReturn($ips)->getMock();
    }

    /** @dataProvider blocked */
    public function test_blocks_unsafe_urls(string $url): void
    {
        $this->expectException(MediaException::class);
        $this->guard()->assertMediaUrl($url);
    }

    public static function blocked(): array
    {
        return [
            ['http://scontent.cdninstagram.com/a.jpg'],
            ['https://127.0.0.1/a.jpg'],
            ['https://localhost/a.jpg'],
            ['https://[::1]/a.jpg'],
            ['https://169.254.169.254/latest/meta-data'],
            ['https://evil.example.com/a.jpg'],
            ['https://scontent.cdninstagram.com.evil.com/a.jpg'],
            ['https://user:pw@scontent.cdninstagram.com/a.jpg'],
            ['https://scontent.cdninstagram.com:8443/a.jpg'],
            ['file:///etc/passwd'],
            ['ftp://scontent.cdninstagram.com/a.jpg'],
        ];
    }

    public function test_allows_allowlisted_public_host(): void
    {
        $t = $this->guard()->assertMediaUrl('https://scontent-xyz.cdninstagram.com/a.jpg');
        $this->assertSame('93.184.216.34', $t['ip']);
    }

    public function test_blocks_allowlisted_host_resolving_to_private_ip(): void
    {
        $this->expectException(MediaException::class);
        $this->guard(['10.0.0.5'])->assertMediaUrl('https://scontent.cdninstagram.com/a.jpg');
    }

    public function test_public_ip_check(): void
    {
        foreach (['127.0.0.1', '10.1.1.1', '192.168.0.1', '172.16.0.1', '169.254.1.1', '0.0.0.0', '100.64.0.1', '::1', 'fe80::1', 'fc00::1'] as $ip) {
            $this->assertFalse(SsrfGuard::isPublicIp($ip), $ip);
        }
        foreach (['8.8.8.8', '93.184.216.34', '2606:4700:4700::1111'] as $ip) {
            $this->assertTrue(SsrfGuard::isPublicIp($ip), $ip);
        }
    }
}
