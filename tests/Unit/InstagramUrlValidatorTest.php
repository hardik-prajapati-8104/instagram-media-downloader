<?php

namespace Tests\Unit;

use App\Exceptions\MediaException;
use App\Services\Instagram\InstagramUrlValidator;
use Tests\TestCase;

class InstagramUrlValidatorTest extends TestCase
{
    public function test_normalizes_valid_urls_and_strips_tracking(): void
    {
        $v = new InstagramUrlValidator();

        $this->assertSame('https://www.instagram.com/reel/ABCde12345/', $v->normalize('https://instagram.com/reels/ABCde12345/?igsh=xyz&utm_source=a'));
        $this->assertSame('https://www.instagram.com/p/ABCde12345/', $v->normalize(' https://www.instagram.com/someuser/p/ABCde12345 '));
        $this->assertSame('https://www.instagram.com/tv/ABCde12345/', $v->normalize('https://m.instagram.com/tv/ABCde12345/#frag'));
    }

    /** @dataProvider badUrls */
    public function test_rejects_bad_urls(string $url, string $code): void
    {
        try {
            (new InstagramUrlValidator())->normalize($url);
            $this->fail('Expected MediaException');
        } catch (MediaException $e) {
            $this->assertSame($code, $e->errorCode);
        }
    }

    public static function badUrls(): array
    {
        return [
            'empty' => ['', 'invalid_url'],
            'http' => ['http://www.instagram.com/p/ABCde12345/', 'invalid_url'],
            'javascript' => ['javascript:alert(1)', 'invalid_url'],
            'userinfo' => ['https://evil@www.instagram.com/p/ABCde12345/', 'invalid_url'],
            'port' => ['https://www.instagram.com:8443/p/ABCde12345/', 'invalid_url'],
            'space' => ['https://www.instagram.com/p/ABC de12345/', 'invalid_url'],
            'lookalike' => ['https://www.instagram.com.evil.com/p/ABCde12345/', 'unsupported_url'],
            'other host' => ['https://example.com/p/ABCde12345/', 'unsupported_url'],
            'profile page' => ['https://www.instagram.com/someuser/', 'unsupported_url'],
            'stories' => ['https://www.instagram.com/stories/user/123/', 'unsupported_url'],
            'file' => ['file:///etc/passwd', 'invalid_url'],
        ];
    }
}
