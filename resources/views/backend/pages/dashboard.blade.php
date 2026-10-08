@extends('backend.layouts.master')

@section('title')
Dashboard - Admin Panel
@endsection

@section('admin-content')
<div class="page-title-area">
    <div class="row align-items-center">
        <div class="col-md-9">
            <ul class="breadcrumbs"><li><span>Dashboard</span></li></ul>
        </div>
        <div class="col-md-3 text-end">@include('backend.layouts.partials.logout')</div>
    </div>
</div>

<div class="row g-3 mb-3">
    @foreach (['total' => 'Total Requests', 'completed' => 'Successful', 'failed' => 'Failed', 'processing' => 'Processing', 'expired' => 'Expired'] as $k => $label)
        <div class="col-6 col-lg"><div class="card p-3 h-100"><div class="text-muted small">{{ $label }}</div><div class="stat">{{ number_format($cards[$k]) }}</div></div></div>
    @endforeach
</div>

<div class="row g-3 mb-3">
    @foreach ($typeCards as $t => $n)
        <div class="col-6 col-md"><div class="card p-3"><div class="text-muted small text-capitalize">{{ $t === 'audio' ? 'Audio conversions' : $t . ' requests' }}</div><div class="h4 mb-0">{{ number_format($n) }}</div></div></div>
    @endforeach
</div>

<div class="row g-3 mb-3">
    <div class="col-lg-6"><div class="card p-3"><h2 class="h6">Requests by day (14 days)</h2><canvas id="byDay" height="180"></canvas></div></div>
    <div class="col-md-6 col-lg-3"><div class="card p-3"><h2 class="h6">By media type</h2><canvas id="byType" height="220"></canvas></div></div>
    <div class="col-md-6 col-lg-3"><div class="card p-3"><h2 class="h6">Success vs failure</h2><canvas id="ratio" height="220"></canvas></div></div>
</div>

<div class="card">
    <div class="card-body">
        <h2 class="h6 mb-3">Recent requests</h2>
        @include('backend.pages.downloads._table', ['downloads' => $recent])
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
    const c = @json($chart);
    const orange = '#f97316', dark = '#111827', soft = '#fdba74', red = '#dc2626';
    new Chart(document.getElementById('byDay'), { type: 'line', data: { labels: c.labels, datasets: [
        { label: 'Total', data: c.total, borderColor: orange, backgroundColor: 'rgba(249,115,22,.15)', fill: true, tension: .3 },
        { label: 'Failed', data: c.failed, borderColor: red, tension: .3 } ] }, options: { plugins: { legend: { position: 'bottom' } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } } });
    new Chart(document.getElementById('byType'), { type: 'bar', data: { labels: c.typeLabels, datasets: [{ data: c.typeValues, backgroundColor: orange }] }, options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } } });
    const ok = c.ok.reduce((a, b) => a + b, 0), failed = c.failed.reduce((a, b) => a + b, 0);
    new Chart(document.getElementById('ratio'), { type: 'doughnut', data: { labels: ['Successful', 'Failed'], datasets: [{ data: [ok, failed], backgroundColor: [orange, dark] }] }, options: { plugins: { legend: { position: 'bottom' } } } });
</script>
@endsection
