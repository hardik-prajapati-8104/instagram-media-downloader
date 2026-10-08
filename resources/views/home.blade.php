@extends('layouts.public')

@section('content')
<section class="bg-gradient-to-b from-brand-50 via-white to-white">
    <div class="mx-auto max-w-4xl px-4 pb-20 pt-14 text-center sm:pt-24">
        <p class="mx-auto mb-5 inline-flex items-center gap-2 rounded-full border border-brand-200 bg-white px-3 py-1 text-xs font-medium text-brand-700"><i data-lucide="shield-check" class="h-3.5 w-3.5"></i> Independent third-party tool</p>
        <h1 class="text-4xl font-bold leading-tight tracking-tight sm:text-6xl">
            <span class="bg-gradient-to-r from-brand-500 to-brand-700 bg-clip-text text-transparent">Instagram Media</span>
            <span class="text-ink">Downloader</span>
        </h1>
        <p class="mx-auto mt-5 max-w-2xl text-base text-gray-600 sm:text-lg">Download supported Instagram videos, reels, photos, carousels, and audio in just a few clicks.</p>
        <div class="mt-10">@include('partials.downloader', ['mode' => 'home'])</div>
    </div>
</section>

<section class="mx-auto max-w-6xl px-4">
    <h2 class="text-center text-2xl font-semibold sm:text-3xl">Everything in one place</h2>
    <div class="mt-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
        @foreach ([['video','Video','video','Save public videos with a live preview.'],['reels','Reels','film','Download Reels as MP4 files.'],['photo','Photo','image','Full-size photos with real dimensions.'],['carousel','Carousel','images','Every slide, or all as one ZIP.'],['audio','Audio','music','Extract the audio track as MP3.']] as [$r,$l,$i,$d])
            <a href="{{ route($r) }}" class="card group p-5 transition hover:-translate-y-0.5 hover:border-brand-300 hover:shadow-lg">
                <span class="grid h-11 w-11 place-items-center rounded-xl bg-brand-50 text-brand-600 transition group-hover:bg-brand-500 group-hover:text-white"><i data-lucide="{{ $i }}" class="h-5 w-5"></i></span>
                <h3 class="mt-4 font-semibold">{{ $l }} Downloader</h3>
                <p class="mt-1.5 text-sm text-gray-500">{{ $d }}</p>
            </a>
        @endforeach
    </div>
</section>

<section class="mx-auto mt-24 max-w-5xl px-4">
    <h2 class="text-center text-2xl font-semibold sm:text-3xl">How it works</h2>
    <ol class="mt-10 grid gap-6 sm:grid-cols-3">
        @foreach ([['Copy the link','Open the post, reel or video you own (or may save) and copy its link.'],['Paste it above','Paste the link and press Download. We validate it and ask the media provider for the real file.'],['Preview & download','Check the preview and details, then download. Files are deleted automatically.']] as $n => [$t,$d])
            <li class="card p-6">
                <span class="grid h-9 w-9 place-items-center rounded-full bg-brand-500 text-sm font-semibold text-white">{{ $n + 1 }}</span>
                <h3 class="mt-4 font-semibold">{{ $t }}</h3>
                <p class="mt-1.5 text-sm text-gray-500">{{ $d }}</p>
            </li>
        @endforeach
    </ol>
</section>

<section class="mx-auto mt-24 max-w-4xl px-4">
    <div class="grid gap-4 sm:grid-cols-3">
        @foreach ([['zap','Fast & queued','Heavy work runs in the background so the page stays responsive.'],['lock','Private by design','Files live in private storage and expire automatically.'],['smartphone','Mobile friendly','Works on phones, tablets and desktops.']] as [$i,$t,$d])
            <div class="rounded-2xl bg-brand-50/60 p-5"><i data-lucide="{{ $i }}" class="h-5 w-5 text-brand-600"></i><h3 class="mt-3 font-semibold">{{ $t }}</h3><p class="mt-1 text-sm text-gray-600">{{ $d }}</p></div>
        @endforeach
    </div>
    <p class="mt-8 text-center text-sm text-gray-500">Questions? Read the <a class="font-medium text-brand-600 hover:underline" href="{{ route('faq') }}">FAQ</a> or the <a class="font-medium text-brand-600 hover:underline" href="{{ route('how-to') }}">step-by-step guide</a>.</p>
</section>
@endsection
