<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\DownloadRequest;
use App\Models\DownloadStatistic;

class DashboardController extends Controller
{
    public function index()
    {
        $counts = DownloadRequest::selectRaw('status, count(*) as c')->groupBy('status')->pluck('c', 'status');

        $byType = DownloadStatistic::selectRaw('media_type, sum(total_requests) as total, sum(successful_requests) as ok')
            ->groupBy('media_type')->get()->keyBy('media_type');

        $daily = DownloadStatistic::where('date', '>=', now()->subDays(13)->toDateString())
            ->selectRaw('date, sum(total_requests) as total, sum(successful_requests) as ok, sum(failed_requests) as failed')
            ->groupBy('date')->orderBy('date')->get();

        $cards = [
            'total' => (int) $counts->sum(),
            'completed' => (int) ($counts['completed'] ?? 0),
            'failed' => (int) ($counts['failed'] ?? 0),
            'processing' => (int) (($counts['processing'] ?? 0) + ($counts['pending'] ?? 0)),
            'expired' => (int) ($counts['expired'] ?? 0),
        ];

        $typeCards = [];
        foreach (DownloadRequest::MEDIA_TYPES as $t) {
            $typeCards[$t] = (int) ($byType[$t]->total ?? 0);
        }

        return view('backend.pages.dashboard', [
            'cards' => $cards,
            'typeCards' => $typeCards,
            'chart' => [
                'labels' => $daily->map(fn ($d) => $d->date instanceof \DateTimeInterface ? $d->date->format('M d') : (string) $d->date)->all(),
                'total' => $daily->pluck('total')->map(fn ($v) => (int) $v)->all(),
                'ok' => $daily->pluck('ok')->map(fn ($v) => (int) $v)->all(),
                'failed' => $daily->pluck('failed')->map(fn ($v) => (int) $v)->all(),
                'typeLabels' => array_keys($typeCards),
                'typeValues' => array_values($typeCards),
            ],
            'recent' => DownloadRequest::latest('id')->paginate(10),
        ]);
    }
}
