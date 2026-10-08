<?php

namespace App\Console\Commands;

use App\Models\DownloadRequest;
use App\Models\DownloadStatistic;
use Illuminate\Console\Command;
use Throwable;

class MediaStats extends Command
{
    protected $signature = 'media:stats {--days=7 : Number of days to include}';

    protected $description = 'Print request statistics';

    public function handle(): int
    {
        try {
            $days = max(1, (int) $this->option('days'));

            $this->info('Requests by status (all time)');
            $this->table(['Status', 'Count'], DownloadRequest::selectRaw('status, count(*) as c')->groupBy('status')->get()->map(fn ($r) => [$r->status, $r->c])->all());

            $this->info("Last {$days} days by media type");
            $rows = DownloadStatistic::where('date', '>=', now()->subDays($days - 1)->toDateString())
                ->selectRaw('media_type, sum(total_requests) t, sum(successful_requests) s, sum(failed_requests) f')
                ->groupBy('media_type')->get()->map(fn ($r) => [$r->media_type, $r->t, $r->s, $r->f])->all();
            $this->table(['Media type', 'Total', 'Successful', 'Failed'], $rows);

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error('Could not read statistics: ' . $e->getMessage());

            return self::FAILURE;
        }
    }
}
