@extends('backend.layouts.master')

@section('title')
Statistics - Admin Panel
@endsection

@section('admin-content')
<div class="page-title-area">
    <div class="row align-items-center">
        <div class="col-md-9"><ul class="breadcrumbs"><li><a href="{{ route('admin.dashboard') }}">Dashboard</a></li><li><span>Statistics</span></li></ul></div>
        <div class="col-md-3 text-end">@include('backend.layouts.partials.logout')</div>
    </div>
</div>
<h3 class="pb-3">Statistics</h3>
<div class="card mb-3"><div class="card-body">
    <form method="GET" class="row g-2">
        <div class="col-md-3"><input type="date" name="from" class="form-control" value="{{ $filters['from'] ?? '' }}"></div>
        <div class="col-md-3"><input type="date" name="to" class="form-control" value="{{ $filters['to'] ?? '' }}"></div>
        <div class="col-md-3"><select name="media_type" class="form-select"><option value="">All types</option>@foreach (array_merge(\App\Models\DownloadRequest::MEDIA_TYPES, ['unknown']) as $t)<option value="{{ $t }}" @selected(($filters['media_type'] ?? '') === $t)>{{ ucfirst($t) }}</option>@endforeach</select></div>
        <div class="col-md-3 d-grid"><button class="btn btn-primary">Filter</button></div>
    </form>
</div></div>
<div class="row g-3 mb-3">
    <div class="col-md-4"><div class="card p-3"><div class="text-muted small">Total</div><div class="stat">{{ number_format($totals->total) }}</div></div></div>
    <div class="col-md-4"><div class="card p-3"><div class="text-muted small">Successful</div><div class="stat">{{ number_format($totals->ok) }}</div></div></div>
    <div class="col-md-4"><div class="card p-3"><div class="text-muted small">Failed</div><div class="stat">{{ number_format($totals->failed) }}</div></div></div>
</div>
<div class="card"><div class="card-body">
    <div class="table-responsive"><table class="table table-striped align-middle">
        <thead class="table-light"><tr><th>Date</th><th>Media type</th><th>Total</th><th>Successful</th><th>Failed</th><th>Success rate</th></tr></thead>
        <tbody>
        @forelse ($rows as $r)
            <tr><td>{{ $r->date->format('Y-m-d') }}</td><td class="text-capitalize">{{ $r->media_type }}</td><td>{{ $r->total_requests }}</td><td>{{ $r->successful_requests }}</td><td>{{ $r->failed_requests }}</td><td>{{ $r->total_requests ? round($r->successful_requests / $r->total_requests * 100) . '%' : '—' }}</td></tr>
        @empty
            <tr><td colspan="6" class="text-center text-muted py-4">No statistics yet.</td></tr>
        @endforelse
        </tbody>
    </table></div>
    {{ $rows->links('pagination::bootstrap-5') }}
</div></div>
@endsection
