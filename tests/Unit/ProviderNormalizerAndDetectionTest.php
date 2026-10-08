<?php

namespace Tests\Unit;

use App\Exceptions\MediaException;
use App\Services\Instagram\DTO\MediaItem;
use App\Services\Instagram\DTO\MediaResult;
use App\Services\Instagram\DTO\ProviderResponse;
use App\Services\Instagram\InstagramUrlValidator;
use App\Services\Instagram\MediaTypeDetectionService;
use App\Services\Instagram\ProviderResponseNormalizer;
use Tests\TestCase;

class ProviderNormalizerAndDetectionTest extends TestCase
{
    private function norm(array $body): MediaResult
    {
        return (new ProviderResponseNormalizer())->normalize(new ProviderResponse(200, $body, 5));
    }

    public function test_normalizes_standard_shape(): void
    {
        $r = $this->norm(['success' => true, 'title' => '<b>Hi</b>', 'thumbnail' => 'https://x.cdninstagram.com/t.jpg',
            'items' => [['type' => 'video', 'url' => 'https://x.cdninstagram.com/v.mp4', 'width' => 1080, 'height' => 1920, 'duration' => 15, 'mime_type' => 'video/mp4']]]);

        $this->assertSame('Hi', $r->title);
        $this->assertCount(1, $r->items);
        $this->assertSame('video', $r->items[0]->type);
        $this->assertSame(1080, $r->items[0]->width);
    }

    public function test_infers_type_from_extension_and_drops_http_urls(): void
    {
        $r = $this->norm(['data' => ['medias' => [
            ['url' => 'https://x.cdninstagram.com/a.jpg'],
            ['url' => 'http://x.cdninstagram.com/insecure.jpg'],
            ['download_url' => 'https://x.cdninstagram.com/b.mp4'],
        ]]]);

        $this->assertCount(2, $r->items);
        $this->assertSame(['image', 'video'], [$r->items[0]->type, $r->items[1]->type]);
    }

    /** @dataProvider failures */
    public function test_rejects_bad_responses(array $body, string $code): void
    {
        try {
            $this->norm($body);
            $this->fail('Expected MediaException');
        } catch (MediaException $e) {
            $this->assertSame($code, $e->errorCode);
        }
    }

    public static function failures(): array
    {
        return [
            'empty' => [[], 'empty_response'],
            'no list' => [['foo' => 'bar'], 'provider_invalid_response'],
            'no usable urls' => [['items' => [['type' => 'video', 'url' => 'ftp://x/y.mp4']]], 'missing_media'],
            'private' => [['success' => false, 'error' => 'This account is private'], 'private_content'],
            'deleted' => [['success' => false, 'error_code' => 'not_found'], 'deleted_content'],
            'rate' => [['error' => 'rate limit exceeded'], 'provider_rate_limited'],
        ];
    }

    public function test_media_type_detection(): void
    {
        $d = new MediaTypeDetectionService(new InstagramUrlValidator());
        $img = new MediaItem('image', 'https://x/a.jpg');
        $vid = new MediaItem('video', 'https://x/a.mp4');

        $this->assertSame('photo', $d->detect(new MediaResult(null, null, null, [$img]), 'https://www.instagram.com/p/ABCde12345/'));
        $this->assertSame('reel', $d->detect(new MediaResult(null, null, null, [$vid]), 'https://www.instagram.com/reel/ABCde12345/'));
        $this->assertSame('video', $d->detect(new MediaResult(null, null, null, [$vid]), 'https://www.instagram.com/p/ABCde12345/'));
        $this->assertSame('carousel', $d->detect(new MediaResult(null, null, null, [$img, $vid]), 'https://www.instagram.com/p/ABCde12345/'));
        $this->assertSame('audio', $d->detect(new MediaResult(null, null, null, [$vid]), 'https://www.instagram.com/p/ABCde12345/', 'audio'));
        // A /p/ URL that really is a single video is "video", never forced to "photo" by URL shape.
    }
}
