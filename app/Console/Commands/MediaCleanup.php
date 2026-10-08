<?php

namespace App\Console\Commands;

use App\Exceptions\MediaException;
use App\Models\DownloadRequest;
use App\Models\MediaProcessingLog;
use App\Services\Instagram\InstagramMediaService;
use App\Services\Instagram\SettingsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class MediaCleanup extends Command
{
    protected $signature = 'media:cleanup {--dry-run : Report what would be removed without deleting anything}';

    protected $description = 'Delete expired media, ZIPs and temp files; fail stale processing requests; prune old logs';

    public function handle(InstagramMediaService $service, SettingsService $settings): int
    {
        try {
            $dry = (bool) $this->option('dry-run');
            $disk = Storage::disk(config('media.disk'));
            $base = trim((string) config('media.directory'), '/');
            $cutoff = now()->subMinutes($settings->retentionMinutes())->getTimestamp();
            $n = ['expired' => 0, 'stale' => 0, 'tmp' => 0, 'orphans' => 0, 'logs' => 0];

            DownloadRequest::dueForExpiry()->chunkById(100, function ($chunk) use (&$n, $dry, $disk, $base) {
                foreach ($chunk as $r) {
                    if (! $dry) {
                        $disk->deleteDirectory("{$base}/{$r->uuid}");
                        $r->media()->delete();
                        $r->update(['status' => DownloadRequest::STATUS_EXPIRED]);
                    }
                    $n['expired']++;
                }
            });

            DownloadRequest::stale((int) config('media.stale_minutes', 30))->chunkById(100, function ($chunk) use (&$n, $dry, $service) {
                foreach ($chunk as $r) {
                    if (! $dry) {
                        $service->fail($r, [], new MediaException('stale_request', 'Marked failed by cleanup (stuck).'));
                    }
                    $n['stale']++;
                }
            });

            foreach ($disk->files("{$base}/tmp") as $file) {
                if ($disk->lastModified($file) < $cutoff) {
                    if (! $dry) {
                        $disk->delete($file);
                    }
                    $n['tmp']++;
                }
            }

            foreach ($disk->directories($base) as $dir) {
                $name = basename($dir);
                if ($name === 'tmp') {
                    continue;
                }
                try {
                    $old = $disk->lastModified($dir) < $cutoff;
                } catch (Throwable) {
                    $old = false;
                }
                if ($old && ! DownloadRequest::where('uuid', $name)->where('status', DownloadRequest::STATUS_COMPLETED)->exists()) {
                    if (! $dry) {
                        $disk->deleteDirectory($dir);
                    }
                    $n['orphans']++;
                }
            }

            if (! $dry) {
                $n['logs'] = MediaProcessingLog::where('created_at', '<', now()->subDays((int) config('media.log_retention_days', 30)))->delete();
            }

            $this->info(($dry ? '[dry-run] ' : '') . sprintf(
                'Expired: %d | Stale failed: %d | Temp files: %d | Orphan dirs: %d | Old logs: %d',
                $n['expired'], $n['stale'], $n['tmp'], $n['orphans'], $n['logs']
            ));
            Log::info('media.cleanup', $n + ['dry_run' => $dry]);

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error('Cleanup failed: ' . $e->getMessage());
            Log::error('media.cleanup.failed', ['error' => $e->getMessage()]);

            return self::FAILURE;
        }
    }
}
