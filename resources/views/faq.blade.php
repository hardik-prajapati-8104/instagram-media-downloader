@extends('layouts.public')

@section('content')
@php
    $faqs = [
        ['Is this an official Instagram tool?', 'No. This is an independent third-party tool and is not affiliated with or endorsed by Instagram or Meta.'],
        ['What can I download?', 'Public videos, reels, photos, carousels and audio (as MP3) — only where the media provider can return them.'],
        ['Can I download private accounts or stories?', 'No. Private content, login-protected content and anything behind access controls is not supported and is never bypassed.'],
        ['Why did my link fail?', 'The post may be private, deleted, or temporarily unavailable from the provider. Check that it is public and try again.'],
        ['Why does the audio tool say there is no audio?', 'Some videos have no audio stream. We only report success when a real MP3 was produced.'],
        ['How long are files kept?', 'Files are stored privately for a short time ('.config('media.retention_minutes').' minutes by default) and then deleted automatically.'],
        ['Do I need to install anything?', 'No. It works in your browser on desktop, tablet and mobile.'],
        ['Am I allowed to download any post?', 'Only content you own or have permission to save and use. You are responsible for respecting copyright and platform terms.'],
    ];
@endphp
<article class="mx-auto max-w-3xl px-4 py-16">
    <h1 class="text-3xl font-bold sm:text-4xl">{{ $meta['h1'] }}</h1>
    <div class="mt-10 divide-y divide-gray-100 rounded-2xl border border-gray-200">
        @foreach ($faqs as [$q, $a])
            <details class="group p-5" @if($loop->first) open @endif>
                <summary class="flex cursor-pointer list-none items-center justify-between gap-4 font-medium">{{ $q }}<i data-lucide="chevron-down" class="h-4 w-4 shrink-0 text-gray-400 transition group-open:rotate-180"></i></summary>
                <p class="mt-3 text-sm text-gray-600">{{ $a }}</p>
            </details>
        @endforeach
    </div>
    <script type="application/ld+json">{!! json_encode(['@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => array_map(fn ($f) => ['@type' => 'Question', 'name' => $f[0], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f[1]]], $faqs)], JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
</article>
@endsection
