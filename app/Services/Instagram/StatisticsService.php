<?php

namespace App\Services\Instagram;

use App\Models\DownloadStatistic;
use Illuminate\Support\Facades\DB;

final class StatisticsService
{
    public function record(string $mediaType, bool $success): void
    {
        $date = today()->toDateString();
        $col = $success ? 'successful_requests' : 'failed_requests';

        DownloadStatistic::insertOrIgnore([[
            'date' => $date, 'media_type' => $mediaType, 'total_requests' => 0,
            'successful_requests' => 0, 'failed_requests' => 0, 'created_at' => now(), 'updated_at' => now(),
        ]]);

        DownloadStatistic::where('date', $date)->where('media_type', $mediaType)->update([
            'total_requests' => DB::raw('total_requests + 1'),
            $col => DB::raw("{$col} + 1"),
            'updated_at' => now(),
        ]);
    }
}
