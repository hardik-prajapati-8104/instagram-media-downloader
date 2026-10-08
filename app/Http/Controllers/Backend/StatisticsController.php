<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\DownloadRequest;
use App\Models\DownloadStatistic;
use Illuminate\Http\Request;

class StatisticsController extends Controller
{
    public function index(Request $request)
    {
        $f = $request->validate([
            'from' => ['nullable', 'date'], 'to' => ['nullable', 'date'],
            'media_type' => ['nullable', 'in:' . implode(',', array_merge(DownloadRequest::MEDIA_TYPES, ['unknown']))],
        ]);

        $query = DownloadStatistic::query()
            ->when($f['from'] ?? null, fn ($q, $v) => $q->whereDate('date', '>=', $v))
            ->when($f['to'] ?? null, fn ($q, $v) => $q->whereDate('date', '<=', $v))
            ->when($f['media_type'] ?? null, fn ($q, $v) => $q->where('media_type', $v));

        $totals = (clone $query)->selectRaw('coalesce(sum(total_requests),0) as total, coalesce(sum(successful_requests),0) as ok, coalesce(sum(failed_requests),0) as failed')->first();

        return view('backend.pages.statistics.index', [
            'rows' => $query->orderByDesc('date')->orderBy('media_type')->paginate(31)->withQueryString(),
            'totals' => $totals,
            'filters' => $f,
        ]);
    }
}
