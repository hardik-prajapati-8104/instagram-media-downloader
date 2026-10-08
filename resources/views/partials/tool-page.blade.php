@php
    $nav = [['video','Video'],['reels','Reels'],['photo','Photo'],['carousel','Carousel'],['audio','Audio']];
    $points = [
        'video' => ['Real file details (resolution, duration, size) from the provider', 'Preview before you download', 'Only the qualities the provider actually returns'],
        'reels' => ['Reels saved as MP4', 'Live preview with duration and size', 'No fake quality options'],
        'photo' => ['Full-size image as returned by the provider', 'Width, height, type and size shown', 'Preview before downloading'],
        'carousel' => ['Every slide listed separately', 'Download one by one or all as ZIP', 'ZIP expires automatically'],
        'audio' => ['Real audio track converted to MP3', 'Clear message if the video has no audio', 'Built-in audio player preview'],
    ][$mode] ?? [];
@endphp
<section class="bg-gradient-to-b from-brand-50 to-white">
    <div class="mx-auto max-w-4xl px-4 pb-16 pt-14 text-center sm:pt-20">
        <h1 class="text-3xl font-bold tracking-tight sm:text-5xl">{{ $meta['h1'] }}</h1>
        <p class="mx-auto mt-4 max-w-2xl text-base text-gray-600 sm:text-lg">{{ $meta['intro'] }}</p>
        <div class="mt-10">@include('partials.downloader', ['mode' => $mode])</div>
    </div>
</section>

<section class="mx-auto mt-4 max-w-4xl px-4">
    <ul class="grid gap-3 sm:grid-cols-3">
        @foreach ($points as $p)
            <li class="card flex items-start gap-3 p-4 text-sm"><i data-lucide="circle-check" class="mt-0.5 h-5 w-5 shrink-0 text-brand-500"></i>{{ $p }}</li>
        @endforeach
    </ul>
    <p class="mt-10 text-center text-sm text-gray-500">
        More tools:
        @foreach ($nav as [$r, $l])
            @unless (request()->routeIs($r)) <a class="font-medium text-brand-600 hover:underline" href="{{ route($r) }}">{{ $l }}</a>@if(!$loop->last) · @endif @endunless
        @endforeach
        · <a class="font-medium text-brand-600 hover:underline" href="{{ route('how-to') }}">How it works</a>
    </p>
</section>
