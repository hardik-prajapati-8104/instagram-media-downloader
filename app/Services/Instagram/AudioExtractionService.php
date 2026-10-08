<?php

namespace App\Services\Instagram;

use App\Exceptions\MediaException;
use Illuminate\Support\Facades\Process;
use Throwable;

/** FFprobe/FFmpeg wrapper. Argument arrays only (no shell), protocol whitelist = local files only. */
class AudioExtractionService
{
    public function __construct(private readonly SettingsService $settings)
    {
    }

    /** @return array{duration:?float,width:?int,height:?int,has_audio:bool,has_video:bool}|null null = ffprobe unavailable */
    public function probe(string $path): ?array
    {
        try {
            $r = Process::timeout(30)->run([
                $this->settings->ffprobe(), '-v', 'error', '-protocol_whitelist', 'file',
                '-print_format', 'json', '-show_format', '-show_streams', $path,
            ]);
        } catch (Throwable) {
            return null;
        }

        if (! $r->successful()) {
            return $r->exitCode() === 127 ? null : ['duration' => null, 'width' => null, 'height' => null, 'has_audio' => false, 'has_video' => false];
        }

        $j = json_decode($r->output(), true);
        if (! is_array($j)) {
            return null;
        }

        $out = ['duration' => isset($j['format']['duration']) ? round((float) $j['format']['duration'], 2) : null,
            'width' => null, 'height' => null, 'has_audio' => false, 'has_video' => false];

        foreach ($j['streams'] ?? [] as $s) {
            if (($s['codec_type'] ?? '') === 'audio') {
                $out['has_audio'] = true;
            }
            if (($s['codec_type'] ?? '') === 'video' && ! $out['has_video']) {
                $out['has_video'] = true;
                $out['width'] = isset($s['width']) ? (int) $s['width'] : null;
                $out['height'] = isset($s['height']) ? (int) $s['height'] : null;
            }
        }

        return $out;
    }

    /** @throws MediaException no_audio | ffmpeg_unavailable */
    public function assertHasAudio(string $path): void
    {
        $this->assertInput($path);
        $info = $this->probe($path);
        if ($info === null) {
            throw new MediaException('ffmpeg_unavailable', 'ffprobe not available or returned invalid output.');
        }
        if (! $info['has_audio']) {
            throw new MediaException('no_audio', 'Source has no audio stream.');
        }
    }

    /**
     * @return array{duration:?float,size:int}
     *
     * @throws MediaException
     */
    public function extractMp3(string $source, string $destination): array
    {
        $this->assertInput($source);

        try {
            $r = Process::timeout((int) config('media.ffmpeg_timeout', 120))->run([
                $this->settings->ffmpeg(), '-nostdin', '-y', '-hide_banner', '-loglevel', 'error',
                '-protocol_whitelist', 'file', '-i', $source,
                '-vn', '-map', '0:a:0', '-c:a', 'libmp3lame', '-q:a', '2', '-f', 'mp3', $destination,
            ]);
        } catch (Throwable $e) {
            throw new MediaException('ffmpeg_unavailable', 'ffmpeg could not be started: ' . $e->getMessage(), false, $e);
        }

        if (! $r->successful()) {
            @unlink($destination);
            $code = $r->exitCode() === 127 ? 'ffmpeg_unavailable' : 'audio_failed';
            throw new MediaException($code, 'ffmpeg exit ' . $r->exitCode() . ': ' . mb_substr($r->errorOutput(), 0, 500));
        }

        clearstatcache(true, $destination);
        $size = is_file($destination) ? (int) filesize($destination) : 0;
        $mime = $size > 0 ? ((new \finfo(FILEINFO_MIME_TYPE))->file($destination) ?: '') : '';

        if ($size === 0 || $mime !== 'audio/mpeg') {
            @unlink($destination);
            throw new MediaException('audio_failed', "Output invalid (size={$size}, mime={$mime}).");
        }

        $probe = $this->probe($destination);
        if ($probe !== null && (! $probe['has_audio'] || ($probe['duration'] ?? 0) <= 0)) {
            @unlink($destination);
            throw new MediaException('audio_failed', 'Output MP3 failed ffprobe validation.');
        }

        return ['duration' => $probe['duration'] ?? null, 'size' => $size];
    }

    private function assertInput(string $path): void
    {
        if (! is_file($path) || ! is_readable($path)) {
            throw new MediaException('audio_failed', 'Input file missing.');
        }
        $size = filesize($path);
        if ($size === false || $size === 0 || $size > $this->settings->maxFileSize()) {
            throw new MediaException('file_too_large', 'Input size invalid: ' . var_export($size, true));
        }
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($path) ?: '';
        if (! str_starts_with($mime, 'video/') && ! str_starts_with($mime, 'audio/')) {
            throw new MediaException('invalid_file', "Input MIME not audio/video: {$mime}");
        }
    }
}
