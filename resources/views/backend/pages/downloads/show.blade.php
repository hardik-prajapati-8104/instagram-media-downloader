@extends('backend.layouts.master')

@section('title')
Download {{ $download->uuid }} - Admin Panel
@endsection

@section('admin-content')
<div class="page-title-area">
    <div class="row align-items-center">
        <div class="col-md-9"><ul class="breadcrumbs"><li><a href="{{ route('admin.dashboard') }}">Dashboard</a></li><li><a href="{{ route('admin.downloads.index') }}">Downloads</a></li><li><span>{{ \Illuminate\Support\Str::limit($download->uuid, 13, '…') }}</span></li></ul></div>
        <div class="col-md-3 text-end"><a href="{{ route('admin.downloads.index') }}" class="btn btn-sm btn-outline-primary">Back</a> @include('backend.layouts.partials.logout')</div>
    </div>
</div>

<div class="card mb-3"><div class="card-body">
    <dl class="row mb-0">
        @foreach ([
            'UUID' => $download->uuid, 'Status' => $download->status, 'Requested mode' => $download->requested_mode, 'Detected type' => $download->media_type ?? '—',
            'Provider' => $download->provider ?? '—', 'Normalized URL' => $download->normalized_url, 'Error code' => $download->error_code ?? '—',
            'Error message' => $download->error_message ?? '—', 'Requested at' => $download->requested_at, 'Completed at' => $download->completed_at ?? '—', 'Expires at' => $download->expires_at ?? '—',
        ] as $k => $v)
            <dt class="col-sm-3">{{ $k }}</dt><dd class="col-sm-9 text-break">{{ $v }}</dd>
        @endforeach
    </dl>
</div></div>

<div class="card mb-3"><div class="card-body">
    <h2 class="h6">Stored files</h2>
    <div class="table-responsive"><table class="table table-sm">
        <thead><tr><th>#</th><th>Type</th><th>File</th><th>MIME</th><th>Size</th><th>Dimensions</th><th>Duration</th></tr></thead>
        <tbody>
        @forelse ($download->media as $m)
            <tr><td>{{ $m->sort_order }}</td><td>{{ $m->media_type }}</td><td>{{ $m->file_name }}</td><td>{{ $m->mime_type }}</td><td>{{ number_format($m->file_size / 1024, 1) }} KB</td><td>{{ $m->width && $m->height ? $m->width.'×'.$m->height : '—' }}</td><td>{{ $m->duration ?? '—' }}</td></tr>
        @empty
            <tr><td colspan="7" class="text-muted">No files stored (failed, pending, or expired).</td></tr>
        @endforelse
        </tbody>
    </table></div>
</div></div>

<div class="card"><div class="card-body">
    <h2 class="h6">Processing log</h2>
    <div class="table-responsive"><table class="table table-sm align-middle">
        <thead><tr><th>Time</th><th>Level</th><th>Event</th><th>Message</th><th>Context</th></tr></thead>
        <tbody>
        @forelse ($download->logs as $l)
            <tr><td class="text-nowrap">{{ $l->created_at?->format('H:i:s') }}</td><td>{{ $l->level }}</td><td>{{ $l->event }}</td><td>{{ $l->message }}</td><td><code class="small text-break">{{ json_encode($l->context, JSON_UNESCAPED_SLASHES) }}</code></td></tr>
        @empty
            <tr><td colspan="5" class="text-muted">No log entries.</td></tr>
        @endforelse
        </tbody>
    </table></div>
</div></div>
@endsection
