<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\MediaProcessingLog;
use Illuminate\Http\Request;

class LogsController extends Controller
{
    public function index(Request $request)
    {
        $f = $request->validate([
            'level' => ['nullable', 'in:debug,info,notice,warning,error,critical'],
            'event' => ['nullable', 'string', 'max:64'],
            'uuid' => ['nullable', 'string', 'max:64'],
            'from' => ['nullable', 'date'], 'to' => ['nullable', 'date'],
        ]);

        $logs = MediaProcessingLog::with('downloadRequest:id,uuid')
            ->when($f['level'] ?? null, fn ($q, $v) => $q->where('level', $v))
            ->when($f['event'] ?? null, fn ($q, $v) => $q->where('event', $v))
            ->when($f['uuid'] ?? null, fn ($q, $v) => $q->whereHas('downloadRequest', fn ($r) => $r->where('uuid', 'like', $v . '%')))
            ->when($f['from'] ?? null, fn ($q, $v) => $q->whereDate('created_at', '>=', $v))
            ->when($f['to'] ?? null, fn ($q, $v) => $q->whereDate('created_at', '<=', $v))
            ->latest('id')->paginate(50)->withQueryString();

        return view('backend.pages.logs.index', ['logs' => $logs, 'filters' => $f]);
    }
}
