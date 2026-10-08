@extends('layouts.public')

@section('content')
<article class="prose-lg mx-auto max-w-3xl space-y-5 px-4 py-16 text-gray-700">
    <h1 class="text-3xl font-bold text-ink sm:text-4xl">{{ $meta['h1'] }}</h1>
    <p>This is an independent third-party tool, not affiliated with Instagram or Meta. Review and adapt this policy to your jurisdiction before launch.</p>
    <h2 class="text-xl font-semibold text-ink">What we process</h2>
    <p>The Instagram link you submit, the media returned for it, technical request data (a one-way hash of your IP address, browser user-agent, timestamps) and aggregated usage statistics. We do not store your raw IP address.</p>
    <h2 class="text-xl font-semibold text-ink">How long we keep it</h2>
    <p>Downloaded or converted files are deleted automatically after {{ config('media.retention_minutes') }} minutes. Request records and processing logs are retained for operational purposes and pruned periodically.</p>
    <h2 class="text-xl font-semibold text-ink">Third parties</h2>
    <p>Links are sent to a configured media provider in order to retrieve the media. Your hosting and CDN providers may process standard server logs.</p>
    <h2 class="text-xl font-semibold text-ink">Cookies</h2>
    <p>We use only essential cookies (session and security tokens).</p>
    <h2 class="text-xl font-semibold text-ink">Contact</h2>
    <p>See the <a class="text-brand-600 hover:underline" href="{{ route('contact') }}">contact page</a>.</p>
    @include('partials.legal')
</article>
@endsection
