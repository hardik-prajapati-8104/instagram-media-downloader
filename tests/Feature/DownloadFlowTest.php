<?php

namespace Tests\Feature;

use App\Exceptions\MediaException;
use App\Models\DownloadedMedia;
use App\Models\DownloadRequest;
use App\Services\Instagram\AudioExtractionService;
use App\Services\Instagram\SsrfGuard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Provider and media hosts are ALWAYS faked here: tests never touch Instagram or the network. */
class DownloadFlowTest extends TestCase
{
    use RefreshDatabase;

    private const URL = 'https://www.instagram.com/p/ABCde12345/';
    private const PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        config([
            'instagram.provider' => 'http', 'instagram.provider_api_url' => 'https://provider.test/api',
            'instagram.provider_api_key' => 'test-key', 'media.rate.limit' => 1000,
        ]);
        $this->partialMock(SsrfGuard::class, fn ($m) => $m->shouldReceive('resolve')->andReturn(['93.184.216.34']));
    }

    private function fakeProvider(array $items, array $extra = []): void
    {
        Http::fake([
            'provider.test/*' => Http::response(['success' => true, 'items' => $items] + $extra),
            'scontent.cdninstagram.com/*' => Http::response(base64_decode(self::PNG), 200, ['Content-Type' => 'image/png']),
        ]);
    }

    private function img(string $name = 'a.jpg'): array
    {
        return ['type' => 'image', 'url' => "https://scontent.cdninstagram.com/{$name}", 'width' => 1, 'height' => 1];
    }

    private function submit(string $url = self::URL, string $type = 'media')
    {
        return $this->postJson('/api/download', ['url' => $url, 'type' => $type]);
    }

    public function test_photo_flow_completes_and_returns_signed_links(): void
    {
        $this->fakeProvider([$this->img()]);

        $res = $this->submit(type: 'photo')->assertOk()->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'completed')->assertJsonPath('data.media_type', 'photo');

        $item = $res->json('data.items.0');
        $this->assertSame('image/png', $item['mime_type']);
        $this->assertStringNotContainsString('storage', $item['download_url']);
        $this->get($item['download_url'])->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->get($item['preview_url'])->assertOk();

        $this->assertDatabaseHas('download_statistics', ['media_type' => 'photo', 'successful_requests' => 1]);
    }

    public function test_status_and_result_endpoints(): void
    {
        $this->fakeProvider([$this->img()]);
        $uuid = $this->submit()->json('data.uuid');

        $this->getJson("/api/download/{$uuid}/status")->assertOk()->assertJsonPath('data.status', 'completed')->assertJsonMissingPath('data.items');
        $this->getJson("/api/download/{$uuid}/result")->assertOk()->assertJsonCount(1, 'data.items');
        $this->getJson('/api/download/' . fake()->uuid() . '/status')->assertNotFound();
    }

    public function test_invalid_and_unsupported_urls(): void
    {
        $this->submit('not a url')->assertStatus(422)->assertJsonPath('error.code', 'invalid_url');
        $this->submit('https://example.com/p/ABCde12345/')->assertStatus(422)->assertJsonPath('error.code', 'unsupported_url');
        $this->submit('http://www.instagram.com/p/ABCde12345/')->assertStatus(422);
        $this->assertDatabaseCount('download_requests', 0);
    }

    public function test_not_configured_provider_is_reported_honestly(): void
    {
        config(['instagram.provider' => null]);

        $this->submit()->assertStatus(503)->assertJsonPath('error.code', 'provider_not_configured')
            ->assertJsonPath('message', 'Media processing is currently unavailable. Please configure a supported media provider.');
        $this->get('/')->assertSee('Media processing is currently unavailable', false);
    }

    public function test_provider_http_failure_never_claims_success(): void
    {
        Http::fake(['provider.test/*' => Http::response('boom', 500)]);

        $this->submit()->assertJsonPath('success', false)->assertJsonPath('error.code', 'provider_unavailable')
            ->assertJsonMissingPath('data.items.0');
        $this->assertDatabaseHas('download_requests', ['status' => 'failed', 'error_code' => 'provider_unavailable']);
        $this->assertDatabaseHas('download_statistics', ['failed_requests' => 1]);
        $this->assertDatabaseHas('media_processing_logs', ['event' => 'request.failed']);
    }

    public function test_provider_timeout_and_invalid_payloads(): void
    {
        Http::fake(['provider.test/*' => fn () => throw new ConnectionException('timed out')]);
        $this->submit()->assertJsonPath('error.code', 'provider_timeout');

        Http::fake(['provider.test/*' => Http::response('<html>nope</html>', 200)]);
        $this->submit('https://www.instagram.com/p/ZZZZZ99999/')->assertJsonPath('error.code', 'provider_invalid_response');

        Http::fake(['provider.test/*' => Http::response(['success' => false, 'error' => 'private account'])]);
        $this->submit('https://www.instagram.com/p/QQQQQ88888/')->assertJsonPath('error.code', 'private_content');
    }

    public function test_user_never_sees_raw_exception_text(): void
    {
        Http::fake(['provider.test/*' => Http::response('SQLSTATE secret-token-123', 500)]);

        $this->submit()->assertDontSee('secret-token-123')->assertDontSee('SQLSTATE');
    }

    public function test_duplicate_submissions_reuse_the_same_request_and_provider_call(): void
    {
        $this->fakeProvider([$this->img()]);

        $a = $this->submit(self::URL . '?igsh=aaa')->json('data.uuid');
        $b = $this->submit(self::URL . '?utm_source=bbb')->json('data.uuid');

        $this->assertSame($a, $b);
        $this->assertDatabaseCount('download_requests', 1);
        Http::assertSentCount(2); // 1 provider call + 1 media download
    }

    public function test_carousel_builds_a_zip_from_real_items_only(): void
    {
        $this->fakeProvider([$this->img('a.jpg'), $this->img('b.jpg')]);

        $res = $this->submit(type: 'carousel')->assertOk()->assertJsonPath('data.media_type', 'carousel')->assertJsonCount(2, 'data.items');
        $this->assertNotNull($res->json('data.archive.download_url'));

        $zip = DownloadedMedia::where('media_type', 'zip')->firstOrFail();
        Storage::disk('local')->assertExists($zip->storage_path);
        $this->get($res->json('data.archive.download_url'))->assertOk();
    }

    public function test_single_item_has_no_zip(): void
    {
        $this->fakeProvider([$this->img()]);

        $this->submit()->assertJsonPath('data.archive', null);
        $this->assertDatabaseMissing('downloaded_media', ['media_type' => 'zip']);
    }

    public function test_ssrf_provider_media_url_is_rejected(): void
    {
        $this->fakeProvider([['type' => 'image', 'url' => 'https://127.0.0.1/secret.jpg']]);
        $this->submit()->assertJsonPath('error.code', 'unsafe_media_url');

        $this->fakeProvider([['type' => 'image', 'url' => 'https://internal.evil.example/a.jpg']], []);
        $this->submit('https://www.instagram.com/p/OTHER12345/')->assertJsonPath('error.code', 'unsafe_media_url');
        Http::assertNotSent(fn ($r) => str_contains($r->url(), '127.0.0.1') || str_contains($r->url(), 'evil.example'));
    }

    public function test_audio_without_audio_stream_fails_clearly(): void
    {
        $this->mock(AudioExtractionService::class, fn ($m) => $m->shouldReceive('assertHasAudio')->andThrow(new MediaException('no_audio', 'none')));
        $this->fakeProvider([['type' => 'video', 'url' => 'https://scontent.cdninstagram.com/v.mp4']]);

        $this->submit(type: 'audio')->assertJsonPath('error.code', 'no_audio')
            ->assertJsonPath('message', 'This video does not contain an accessible audio stream.');
        $this->assertDatabaseMissing('downloaded_media', ['media_type' => 'audio']);
    }

    public function test_audio_conversion_failure_and_success(): void
    {
        $this->fakeProvider([['type' => 'video', 'url' => 'https://scontent.cdninstagram.com/v.mp4']]);

        $this->mock(AudioExtractionService::class, function ($m) {
            $m->shouldReceive('assertHasAudio')->once();
            $m->shouldReceive('extractMp3')->once()->andThrow(new MediaException('audio_failed', 'ffmpeg exit 1'));
        });
        $this->submit(type: 'audio')->assertJsonPath('error.code', 'audio_failed');
        $this->assertDatabaseMissing('downloaded_media', ['media_type' => 'audio']);
    }

    public function test_audio_success_with_mocked_ffmpeg(): void
    {
        $this->fakeProvider([['type' => 'video', 'url' => 'https://scontent.cdninstagram.com/v.mp4']]);
        $this->mock(AudioExtractionService::class, function ($m) {
            $m->shouldReceive('assertHasAudio')->once();
            $m->shouldReceive('extractMp3')->once()->andReturnUsing(function ($src, $dest) {
                file_put_contents($dest, 'fake-mp3');

                return ['duration' => 5.5, 'size' => 8];
            });
        });

        $this->submit(type: 'audio')->assertOk()->assertJsonPath('data.media_type', 'audio')->assertJsonPath('data.items.0.mime_type', 'audio/mpeg');
    }

    public function test_download_authorization_and_expiry(): void
    {
        $this->fakeProvider([$this->img()]);
        $res = $this->submit();
        $media = DownloadedMedia::firstOrFail();

        $this->get("/api/media/{$media->id}/download")->assertForbidden();                      // unsigned
        $this->get($res->json('data.items.0.download_url') . 'x')->assertForbidden();            // tampered signature

        $url = $res->json('data.items.0.download_url');
        $media->update(['storage_path' => 'media/../../.env']);                                    // path traversal attempt
        $this->get($url)->assertNotFound();
        $media->update(['storage_path' => 'media/' . DownloadRequest::firstOrFail()->uuid . '/1.png']);

        DownloadRequest::query()->update(['expires_at' => now()->subMinute()]);
        $this->get($url)->assertStatus(410);
        $this->getJson('/api/download/' . $res->json('data.uuid') . '/result')->assertStatus(410)->assertJsonPath('error.code', 'expired_result');
    }

    public function test_rate_limit_applies_to_download_endpoint(): void
    {
        config(['media.rate.limit' => 2, 'media.rate.window' => 1]);
        $this->fakeProvider([$this->img()]);

        $this->submit()->assertOk();
        $this->submit()->assertOk();
        $this->submit()->assertStatus(429)->assertJsonPath('error.code', 'rate_limit');
    }

    public function test_retry_only_for_failed_requests_and_cleanup_command(): void
    {
        Http::fake(['provider.test/*' => Http::response('x', 500)]);
        $uuid = $this->submit()->json('data.uuid');

        $this->fakeProvider([$this->img()]);
        $this->postJson("/api/download/{$uuid}/retry")->assertStatus(200)->assertJsonPath('data.status', 'completed');
        $this->postJson("/api/download/{$uuid}/retry")->assertStatus(409); // already completed

        DownloadRequest::query()->update(['expires_at' => now()->subMinute()]);
        $this->artisan('media:cleanup')->assertSuccessful();
        $this->assertDatabaseHas('download_requests', ['uuid' => $uuid, 'status' => 'expired']);
        $this->assertDatabaseCount('downloaded_media', 0);
        Storage::disk('local')->assertMissing("media/{$uuid}/1.png");
    }

    public function test_public_pages_render_with_seo_basics(): void
    {
        foreach (['/', '/video', '/reels', '/photo', '/carousel', '/audio', '/how-to-download', '/faq', '/privacy-policy', '/terms', '/contact'] as $uri) {
            $this->get($uri)->assertOk()->assertSee('<link rel="canonical"', false)->assertSee('property="og:title"', false);
        }
        $this->get('/sitemap.xml')->assertOk()->assertSee('/reels', false);
        $this->get('/robots.txt')->assertOk()->assertSee('Sitemap:');
        $this->get('/')->assertSee('Download only content that you own', false);
    }

    public function test_admin_requires_authentication(): void
    {
        $this->get('/admin/dashboard')->assertRedirect('/admin/login');
        $this->get('/admin/settings')->assertRedirect('/admin/login');
    }
}
