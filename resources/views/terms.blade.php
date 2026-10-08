@extends('layouts.public')

@section('content')
<article class="mx-auto max-w-3xl space-y-5 px-4 py-16 text-gray-700">
    <h1 class="text-3xl font-bold text-ink sm:text-4xl">{{ $meta['h1'] }}</h1>
    <p>By using this website you agree to these terms. Review and adapt them with legal advice before launch.</p>
    <h2 class="text-xl font-semibold text-ink">Permitted use</h2>
    <p>You may only download content that you own or have permission to save and use. You are solely responsible for complying with copyright law and the terms of the platform the content comes from.</p>
    <h2 class="text-xl font-semibold text-ink">Prohibited use</h2>
    <p>Do not use this service to access private or protected content, to bypass technical protections, or to overload or abuse the service. Automated abuse is rate limited and may be blocked.</p>
    <h2 class="text-xl font-semibold text-ink">No affiliation</h2>
    <p>This is an independent third-party tool. It is not affiliated with, endorsed by or sponsored by Instagram or Meta.</p>
    <h2 class="text-xl font-semibold text-ink">No warranty</h2>
    <p>The service is provided “as is”. Availability depends on third-party providers and may change without notice.</p>
    @include('partials.legal')
</article>
@endsection
