<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $meta['title'] ?? config('seo.site_name') }}</title>
    <meta name="description" content="{{ $meta['description'] ?? '' }}">
    <link rel="canonical" href="{{ url()->current() }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#f97316">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ config('seo.site_name') }}">
    <meta property="og:title" content="{{ $meta['title'] ?? config('seo.site_name') }}">
    <meta property="og:description" content="{{ $meta['description'] ?? '' }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta name="twitter:card" content="summary">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lexend+Deca:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-white">
@php
    $nav = [
        ['video', 'Video', 'video'],
        ['reels', 'Reels', 'film'],
        ['photo', 'Photo', 'image'],
        ['carousel', 'Carousel', 'images'],
        ['audio', 'Audio', 'music'],
    ];
@endphp
<a href="#main" class="sr-only focus:not-sr-only focus:absolute focus:left-3 focus:top-3 focus:z-50 focus:rounded-lg focus:bg-white focus:px-3 focus:py-2">Skip to content</a>

<header class="sticky top-0 z-40 border-b border-gray-100 bg-white/90 backdrop-blur" x-data="{ open: false }" @keydown.escape.window="open = false">
    <div class="mx-auto flex h-16 max-w-6xl items-center justify-between px-4">
        <a href="{{ route('home') }}" class="flex items-center gap-2.5 font-semibold">
            <span class="grid h-9 w-9 place-items-center rounded-xl bg-gradient-to-br from-brand-400 to-brand-600 text-white shadow-md shadow-brand-500/30"><i data-lucide="download" class="h-5 w-5"></i></span>
            <span class="hidden text-[15px] sm:inline">Instagram Media Downloader</span>
            <span class="text-[15px] sm:hidden">IG Downloader</span>
        </a>

        <nav class="hidden items-center gap-1 md:flex" aria-label="Primary">
            @foreach ($nav as [$route, $label, $ico])
                <a href="{{ route($route) }}" class="nav-link" @if (request()->routeIs($route)) aria-current="page" @endif>
                    <i data-lucide="{{ $ico }}" class="h-4 w-4"></i>{{ $label }}
                </a>
            @endforeach
        </nav>

        <button type="button" class="grid h-11 w-11 place-items-center rounded-xl text-gray-700 hover:bg-brand-50 md:hidden" @click="open = !open" :aria-expanded="open" aria-label="Toggle menu">
            <i data-lucide="menu" class="h-6 w-6"></i>
        </button>
    </div>

    <nav x-show="open" x-cloak x-transition.opacity class="border-t border-gray-100 bg-white px-4 py-3 md:hidden" aria-label="Mobile">
        <div class="grid gap-1">
            @foreach ($nav as [$route, $label, $ico])
                <a href="{{ route($route) }}" class="nav-link min-h-11 text-base" @if (request()->routeIs($route)) aria-current="page" @endif>
                    <i data-lucide="{{ $ico }}" class="h-5 w-5"></i>{{ $label }}
                </a>
            @endforeach
        </div>
    </nav>
</header>

<main id="main">
    @yield('content')
</main>

<footer class="mt-24 border-t border-gray-100 bg-brand-50/40">
    <div class="mx-auto max-w-6xl px-4 py-12">
        @include('partials.legal')
        <div class="mt-8 grid gap-8 sm:grid-cols-3">
            <div>
                <p class="font-semibold">Instagram Media Downloader</p>
                <p class="mt-2 text-sm text-gray-500">Independent third-party tool.</p>
            </div>
            <div>
                <p class="text-sm font-semibold">Tools</p>
                <ul class="mt-3 space-y-2 text-sm text-gray-600">
                    @foreach ($nav as [$route, $label])
                        <li><a class="hover:text-brand-600" href="{{ route($route) }}">Instagram {{ $label }} Downloader</a></li>
                    @endforeach
                </ul>
            </div>
            <div>
                <p class="text-sm font-semibold">Company</p>
                <ul class="mt-3 space-y-2 text-sm text-gray-600">
                    <li><a class="hover:text-brand-600" href="{{ route('how-to') }}">How to download</a></li>
                    <li><a class="hover:text-brand-600" href="{{ route('faq') }}">FAQ</a></li>
                    <li><a class="hover:text-brand-600" href="{{ route('privacy') }}">Privacy Policy</a></li>
                    <li><a class="hover:text-brand-600" href="{{ route('terms') }}">Terms</a></li>
                    <li><a class="hover:text-brand-600" href="{{ route('contact') }}">Contact</a></li>
                </ul>
            </div>
        </div>
        <p class="mt-10 text-xs text-gray-400">© {{ date('Y') }} {{ config('app.name') }}. Not affiliated with Instagram or Meta.</p>
    </div>
</footer>
</body>
</html>
