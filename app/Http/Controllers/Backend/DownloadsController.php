<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\DownloadRequest;
use Illuminate\Http\Request;

class DownloadsController extends Controller
{
    public function index(Request $request)
    {
        $f = $request->validate([
            'from' => ['nullable', 'date'], 'to' => ['nullable', 'date'],
            'media_type' => ['nullable', 'in:' . implode(',', DownloadRequest::MEDIA_TYPES)],
            'status' => ['nullable', 'in:' . implode(',', DownloadRequest::STATUSES)],
            'q' => ['nullable', 'string', 'max:200'],
        ]);

        $query = DownloadRequest::query()
            ->when($f['from'] ?? null, fn ($q, $v) => $q->whereDate('requested_at', '>=', $v))
            ->when($f['to'] ?? null, fn ($q, $v) => $q->whereDate('requested_at', '<=', $v))
            ->when($f['media_type'] ?? null, fn ($q, $v) => $q->where('media_type', $v))
            ->when($f['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when($f['q'] ?? null, fn ($q, $v) => $q->where(fn ($w) => $w->where('uuid', 'like', $v . '%')->orWhere('normalized_url', 'like', '%' . addcslashes($v, '%_') . '%')));

        return view('backend.pages.downloads.index', [
            'downloads' => $query->latest('id')->paginate(20)->withQueryString(),
            'filters' => $f,
        ]);
    }

    public function show(string $uuid)
    {
        $download = DownloadRequest::with(['media', 'logs' => fn ($q) => $q->orderBy('id')])->where('uuid', $uuid)->firstOrFail();

        return view('backend.pages.downloads.show', compact('download'));
    }
}
