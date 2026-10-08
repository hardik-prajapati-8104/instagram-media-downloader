@extends('backend.layouts.master')

@section('title')
Downloads - Admin Panel
@endsection

@section('admin-content')
<div class="page-title-area">
    <div class="row align-items-center">
        <div class="col-md-9"><ul class="breadcrumbs"><li><a href="{{ route('admin.dashboard') }}">Dashboard</a></li><li><span>Downloads</span></li></ul></div>
        <div class="col-md-3 text-end">@include('backend.layouts.partials.logout')</div>
    </div>
</div>

<h3 class="pb-3">Download Requests</h3>
<div class="card mb-3"><div class="card-body">
    @include('backend.layouts.partials.messages')
    <form method="GET" class="row g-2">
        <div class="col-md-2"><input type="date" name="from" class="form-control" value="{{ $filters['from'] ?? '' }}" aria-label="From"></div>
        <div class="col-md-2"><input type="date" name="to" class="form-control" value="{{ $filters['to'] ?? '' }}" aria-label="To"></div>
        <div class="col-md-2"><select name="media_type" class="form-select"><option value="">All types</option>@foreach (\App\Models\DownloadRequest::MEDIA_TYPES as $t)<option value="{{ $t }}" @selected(($filters['media_type'] ?? '') === $t)>{{ ucfirst($t) }}</option>@endforeach</select></div>
        <div class="col-md-2"><select name="status" class="form-select"><option value="">All statuses</option>@foreach (\App\Models\DownloadRequest::STATUSES as $t)<option value="{{ $t }}" @selected(($filters['status'] ?? '') === $t)>{{ ucfirst($t) }}</option>@endforeach</select></div>
        <div class="col-md-3"><input name="q" class="form-control" placeholder="UUID or URL" value="{{ $filters['q'] ?? '' }}"></div>
        <div class="col-md-1 d-grid"><button class="btn btn-primary">Filter</button></div>
    </form>
</div></div>

<div class="card"><div class="card-body">@include('backend.pages.downloads._table', ['downloads' => $downloads])</div></div>
@endsection
