@extends('backend.layouts.master')

@section('title')
Settings - Admin Panel
@endsection

@section('admin-content')
<div class="page-title-area">
    <div class="row align-items-center">
        <div class="col-md-7"><ul class="breadcrumbs"><li><a href="{{ route('admin.dashboard') }}">Dashboard</a></li><li><span>Settings</span></li></ul></div>
        <div class="col-md-3 text-end"><button type="button" class="btn btn-primary" onclick="document.getElementById('submitForm').click()"><i class="bi bi-save"></i> Save</button></div>
        <div class="col-md-2 text-end">@include('backend.layouts.partials.logout')</div>
    </div>
</div>
<h3 class="pb-3">Settings</h3>
<div class="card"><div class="card-body">
    @include('backend.layouts.partials.messages')
    <form method="POST" action="{{ route('admin.settings.update') }}" autocomplete="off">
        @csrf @method('PUT')
        <h2 class="h6 text-uppercase text-muted">Provider</h2>
        <div class="row g-3 mb-4">
            <div class="col-md-4"><label class="form-label" for="provider">Provider</label>
                <select class="form-select" id="provider" name="provider"><option value="">— none —</option>@foreach ($providers as $p)<option value="{{ $p }}" @selected(old('provider', $s->providerName()) === $p)>{{ $p }}</option>@endforeach</select></div>
            <div class="col-md-8"><label class="form-label" for="provider_url">Provider endpoint (https)</label>
                <input class="form-control" id="provider_url" name="provider_url" type="url" value="{{ old('provider_url', $s->providerUrl()) }}" placeholder="https://api.saveapi.org/v1/download"></div>
            <div class="col-md-8"><label class="form-label" for="provider_api_key">API key / credentials</label>
                <input class="form-control" id="provider_api_key" name="provider_api_key" type="password" autocomplete="new-password" placeholder="{{ $s->maskedProviderKey() ?? 'Not set' }}">
                <div class="form-text">Stored encrypted. Leave blank to keep the current value; the full key is never shown again.</div>
                <div class="alert alert-info mt-2 mb-0 py-2 small">For this project, use a SaveAPI <code>sk_live_...</code> key. Create one from the SaveAPI dashboard, then paste it here. Meta app credentials are not a generic public Instagram media-download key.</div>
                @if ($s->providerKey())<div class="form-check mt-2"><input class="form-check-input" type="checkbox" name="clear_api_key" id="clear_api_key" value="1"><label class="form-check-label" for="clear_api_key">Remove stored key</label></div>@endif</div>
            <div class="col-md-4"><label class="form-label" for="provider_timeout">Request timeout (seconds)</label><input class="form-control" id="provider_timeout" name="provider_timeout" type="number" min="5" max="120" value="{{ old('provider_timeout', $s->providerTimeout()) }}" required></div>
        </div>

        <h2 class="h6 text-uppercase text-muted">Limits & retention</h2>
        <div class="row g-3 mb-4">
            <div class="col-md-3"><label class="form-label" for="rate_limit">Rate limit (requests)</label><input class="form-control" id="rate_limit" name="rate_limit" type="number" min="1" value="{{ old('rate_limit', $s->rateLimit()) }}" required></div>
            <div class="col-md-3"><label class="form-label" for="rate_window">Per window (minutes)</label><input class="form-control" id="rate_window" name="rate_window" type="number" min="1" max="60" value="{{ old('rate_window', $s->rateWindow()) }}" required></div>
            <div class="col-md-3"><label class="form-label" for="retention_minutes">Media retention (minutes)</label><input class="form-control" id="retention_minutes" name="retention_minutes" type="number" min="5" value="{{ old('retention_minutes', $s->retentionMinutes()) }}" required></div>
            <div class="col-md-3"><label class="form-label" for="max_file_size_mb">Max file size (MB)</label><input class="form-control" id="max_file_size_mb" name="max_file_size_mb" type="number" min="1" max="1024" value="{{ old('max_file_size_mb', (int) round($s->maxFileSize() / 1048576)) }}" required></div>
        </div>

        <h2 class="h6 text-uppercase text-muted">Queue & FFmpeg</h2>
        <div class="row g-3 mb-4">
            <div class="col-md-3"><label class="form-label">Queue connection</label><input class="form-control" value="{{ $queueConnection }}" disabled><div class="form-text">Set via <code>QUEUE_CONNECTION</code> in .env.</div></div>
            <div class="col-md-3"><label class="form-label" for="queue_name">Queue name</label><input class="form-control" id="queue_name" name="queue_name" value="{{ old('queue_name', $s->queueName()) }}" required><div class="form-text">Your worker must listen to it.</div></div>
            <div class="col-md-3"><label class="form-label" for="ffmpeg_binary">FFmpeg path</label><input class="form-control" id="ffmpeg_binary" name="ffmpeg_binary" value="{{ old('ffmpeg_binary', $s->ffmpeg()) }}" required></div>
            <div class="col-md-3"><label class="form-label" for="ffprobe_binary">FFprobe path</label><input class="form-control" id="ffprobe_binary" name="ffprobe_binary" value="{{ old('ffprobe_binary', $s->ffprobe()) }}" required></div>
        </div>

        <div class="text-center"><button type="submit" id="submitForm" class="btn btn-primary px-4"><i class="bi bi-save"></i> Save settings</button></div>
    </form>
</div></div>
@endsection
