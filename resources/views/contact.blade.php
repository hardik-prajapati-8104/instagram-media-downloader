@extends('layouts.public')

@section('content')
<article class="mx-auto max-w-xl px-4 py-16">
    <h1 class="text-3xl font-bold sm:text-4xl">{{ $meta['h1'] }}</h1>
    @if (config('media.contact_email'))
        <p class="mt-3 text-gray-600">Questions, takedown requests or feedback? Send us a message.</p>
        @if (session('status'))
            <div class="mt-6 rounded-xl border border-green-200 bg-green-50 p-4 text-sm text-green-800" role="status">{{ session('status') }}</div>
        @endif
        <form method="POST" action="{{ route('contact.send') }}" class="card mt-6 space-y-4 p-5">
            @csrf
            <div class="hidden" aria-hidden="true"><label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
            @foreach ([['name','Name','text'],['email','Email','email']] as [$f,$l,$t])
                <div>
                    <label for="{{ $f }}" class="text-sm font-medium">{{ $l }}</label>
                    <input id="{{ $f }}" name="{{ $f }}" type="{{ $t }}" value="{{ old($f) }}" required class="mt-1 h-11 w-full rounded-xl border border-gray-200 px-3 focus:border-brand-400 focus:outline-none focus:ring-2 focus:ring-brand-200">
                    @error($f)<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
            @endforeach
            <div>
                <label for="message" class="text-sm font-medium">Message</label>
                <textarea id="message" name="message" rows="5" required class="mt-1 w-full rounded-xl border border-gray-200 p-3 focus:border-brand-400 focus:outline-none focus:ring-2 focus:ring-brand-200">{{ old('message') }}</textarea>
                @error('message')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <button class="btn-primary w-full" type="submit"><i data-lucide="mail" class="h-4 w-4"></i> Send message</button>
        </form>
    @else
        <p class="mt-3 text-gray-600">The contact form has not been configured yet (set <code>CONTACT_EMAIL</code> in the environment).</p>
    @endif
</article>
@endsection
