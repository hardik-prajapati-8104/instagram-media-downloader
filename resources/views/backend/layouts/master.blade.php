<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin Panel')</title>
    <link href="https://fonts.googleapis.com/css2?family=Lexend+Deca:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        :root { --bs-primary: #f97316; --bs-primary-rgb: 249,115,22; --bs-link-color: #ea580c; --bs-link-hover-color: #c2410c; --bs-font-sans-serif: "Lexend Deca", sans-serif; }
        body { font-family: "Lexend Deca", sans-serif; background: #fff7ed; color: #111827; }
        .btn-primary, .btn-add { --bs-btn-bg: #f97316; --bs-btn-border-color: #f97316; --bs-btn-hover-bg: #ea580c; --bs-btn-hover-border-color: #ea580c; --bs-btn-active-bg: #c2410c; --bs-btn-active-border-color: #c2410c; --bs-btn-color:#fff; --bs-btn-hover-color:#fff; }
        .btn-outline-primary { --bs-btn-color: #ea580c; --bs-btn-border-color: #f97316; --bs-btn-hover-bg: #f97316; --bs-btn-hover-border-color: #f97316; --bs-btn-hover-color:#fff; }
        .sidebar { width: 240px; min-height: 100vh; background: #fff; border-right: 1px solid #fed7aa; position: fixed; inset: 0 auto 0 0; padding: 1.25rem 1rem; z-index: 1030; }
        .sidebar .brand { font-weight: 700; color: #ea580c; display:flex; align-items:center; gap:.5rem; margin-bottom: 1.5rem; text-decoration:none; }
        .sidebar a.item { display:flex; align-items:center; gap:.6rem; padding:.6rem .8rem; border-radius:.6rem; color:#374151; text-decoration:none; font-size:.9rem; margin-bottom:.15rem; }
        .sidebar a.item:hover, .sidebar a.item.active { background:#ffedd5; color:#c2410c; }
        .main { margin-left: 240px; padding: 0 1.5rem 3rem; }
        .page-title-area { padding: 1rem 0; border-bottom: 1px solid #fed7aa; margin-bottom: 1rem; }
        .breadcrumbs { list-style:none; margin:0; padding:0; display:flex; gap:.5rem; font-size:.85rem; }
        .breadcrumbs li + li::before { content:"/"; margin-right:.5rem; color:#9ca3af; }
        .card { border:1px solid #fed7aa; border-radius: 1rem; }
        .stat { font-size: 1.75rem; font-weight: 700; color: #ea580c; }
        .text-error { color:#dc2626; font-size:.8rem; }
        .badge-status { text-transform: capitalize; }
        @media (max-width: 991px) { .sidebar { position: static; width:100%; min-height:auto; border-right:0; border-bottom:1px solid #fed7aa; } .main { margin-left:0; padding: 0 1rem 3rem; } }
    </style>
    @yield('styles')
</head>
<body>
    <aside class="sidebar">
        <a class="brand" href="{{ route('admin.dashboard') }}">
            {{-- <i class="bi bi-download"></i> IG Downloader --}}
            <img src ="{{ url('public/logo.png') }}" alt="Logo" width="200px;" height="100%;" />
        </a>
        @php $links = [['admin.dashboard','bi-speedometer2','Dashboard'],['admin.downloads.index','bi-cloud-download','Downloads'],['admin.logs','bi-journal-text','Logs'],['admin.statistics','bi-bar-chart','Statistics'],['admin.settings','bi-gear','Settings']]; @endphp
        @foreach ($links as [$r,$i,$l])
            <a class="item {{ request()->routeIs($r . '*') ? 'active' : '' }}" href="{{ route($r) }}"><i class="bi {{ $i }}"></i>{{ $l }}</a>
        @endforeach
        <hr>
        <a class="item" href="{{ route('home') }}" target="_blank" rel="noopener"><i class="bi bi-box-arrow-up-right"></i>View site</a>
    </aside>
    <div class="main">
        @yield('admin-content')
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @yield('scripts')
</body>
</html>
