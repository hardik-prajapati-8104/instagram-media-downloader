@extends('backend.layouts.master')

@section('title')
Logs - Admin Panel
@endsection

@section('admin-content')
<div class="page-title-area">
    <div class="row align-items-center">
        <div class="col-md-9"><ul class="breadcrumbs"><li><a href="{{ route('admin.dashboard') }}">Dashboard</a></li><li><span>Logs</span></li></ul></div>
        <div class="col-md-3 text-end">@include('backend.layouts.partials.logout')</div>
    </div>
</div>
<h3 class="pb-3">Processing Logs</h3>
<div class="card mb-3"><div class="card-body">
    <form method="GET" class="row g-2">
        <div class="col-md-2"><select name="level" class="form-select"><option value="">All levels</option>@foreach (['debug','info','notice','warning','error','critical'] as $l)<option value="{{ $l }}" @selected(($filters['level'] ?? '') === $l)>{{ ucfirst($l) }}</option>@endforeach</select></div>
        <div class="col-md-2"><input name="event" class="form-control" placeholder="Event" value="{{ $filters['event'] ?? '' }}"></div>
        <div class="col-md-3"><input name="uuid" class="form-control" placeholder="UUID" value="{{ $filters['uuid'] ?? '' }}"></div>
        <div class="col-md-2"><input type="date" name="from" class="form-control" value="{{ $filters['from'] ?? '' }}"></div>
        <div class="col-md-2"><input type="date" name="to" class="form-control" value="{{ $filters['to'] ?? '' }}"></div>
        <div class="col-md-1 d-grid"><button class="btn btn-primary">Filter</button></div>
    </form>
</div></div>
<div class="card"><div class="card-body">
    <div class="table-responsive"><table class="table table-striped table-sm align-middle">
        <thead class="table-light"><tr><th>Time</th><th>Level</th><th>Event</th><th>UUID</th><th>Message</th></tr></thead>
        <tbody>
        @forelse ($logs as $l)
            <tr><td class="text-nowrap">{{ $l->created_at?->format('Y-m-d H:i:s') }}</td><td><span class="badge text-bg-{{ in_array($l->level, ['error','critical']) ? 'danger' : ($l->level === 'warning' ? 'warning' : 'secondary') }}">{{ $l->level }}</span></td><td>{{ $l->event }}</td>
                <td>@if($l->downloadRequest)<a href="{{ route('admin.downloads.show', $l->downloadRequest->uuid) }}"><code>{{ \Illuminate\Support\Str::limit($l->downloadRequest->uuid, 8, '…') }}</code></a>@else — @endif</td><td class="text-break">{{ $l->message }}</td></tr>
        @empty
            <tr><td colspan="5" class="text-center text-muted py-4">No log entries.</td></tr>
        @endforelse
        </tbody>
    </table></div>
    {{ $logs->links('pagination::bootstrap-5') }}
</div></div>
@endsection
