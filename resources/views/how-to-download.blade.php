@extends('layouts.public')

@section('content')
<article class="mx-auto max-w-3xl px-4 py-16">
    <h1 class="text-3xl font-bold sm:text-4xl">{{ $meta['h1'] }}</h1>
    <p class="mt-4 text-gray-600">Follow these steps. You need a link to a <strong>public</strong> post you own or have permission to save.</p>
    <ol class="mt-10 space-y-6">
        <li class="card p-5"><h2 class="font-semibold">1. Copy the link</h2><p class="mt-1 text-sm text-gray-600">In Instagram tap <em>Share</em> → <em>Copy link</em>, or copy the address from your browser.</p></li>
        <li class="card p-5"><h2 class="font-semibold">2. Choose a tool</h2><p class="mt-1 text-sm text-gray-600">Use the <a class="text-brand-600 hover:underline" href="{{ route('home') }}">home page</a> for any type, or a specific tool: <a class="text-brand-600 hover:underline" href="{{ route('video') }}">video</a>, <a class="text-brand-600 hover:underline" href="{{ route('reels') }}">reels</a>, <a class="text-brand-600 hover:underline" href="{{ route('photo') }}">photo</a>, <a class="text-brand-600 hover:underline" href="{{ route('carousel') }}">carousel</a> or <a class="text-brand-600 hover:underline" href="{{ route('audio') }}">audio</a>.</p></li>
        <li class="card p-5"><h2 class="font-semibold">3. Paste and press Download</h2><p class="mt-1 text-sm text-gray-600">Use the Paste button or press Ctrl/Cmd+V. The actual media type is detected from what the provider returns.</p></li>
        <li class="card p-5"><h2 class="font-semibold">4. Preview and save</h2><p class="mt-1 text-sm text-gray-600">Check the preview and details, then press Download. Results expire automatically; if one does, submit the link again.</p></li>
    </ol>
    <div class="mt-10">@include('partials.legal')</div>
</article>
@endsection
