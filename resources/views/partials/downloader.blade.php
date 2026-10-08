{{-- Props: $mode (home|video|reels|photo|carousel|audio). $providerReady is injected by a view composer. --}}
@php
    $type = in_array($mode, ['audio']) ? 'audio' : ($mode === 'home' ? 'media' : $mode);
    $buttonLabel = $mode === 'audio' ? 'Convert' : 'Download';
@endphp
<div data-downloader data-type="{{ $type }}" data-endpoint="{{ route('api.download.store') }}" class="mx-auto w-full max-w-3xl">
    @unless ($providerReady)
        <div class="mb-4 flex gap-3 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-left text-sm text-amber-900" role="status">
            <i data-lucide="circle-alert" class="mt-0.5 h-5 w-5 shrink-0"></i>
            <p>Media processing is currently unavailable. Please configure a supported media provider.</p>
        </div>
    @endunless

    <form data-form novalidate class="card flex flex-col gap-3 p-3 sm:flex-row sm:items-center sm:p-2.5" @unless($providerReady) inert @endunless>
        <label for="ig-url-{{ $mode }}" class="sr-only">Instagram link</label>
        <div class="flex min-w-0 flex-1 items-center gap-2 rounded-xl bg-gray-50 px-3 ring-1 ring-gray-200 focus-within:bg-white focus-within:ring-2 focus-within:ring-brand-400">
            <i data-lucide="link" class="h-5 w-5 shrink-0 text-gray-400"></i>
            <input id="ig-url-{{ $mode }}" data-input type="url" inputmode="url" autocomplete="off" autocapitalize="off" spellcheck="false"
                   placeholder="Paste Instagram Link Here..." class="h-12 min-w-0 flex-1 bg-transparent text-base outline-none placeholder:text-gray-400">
            <button type="button" data-clear class="btn-ghost hidden h-9 w-9 !p-0" aria-label="Clear"><i data-lucide="x" class="h-4 w-4"></i></button>
            <button type="button" data-paste class="btn-ghost shrink-0 !px-2.5"><i data-lucide="clipboard-paste" class="h-4 w-4"></i><span class="hidden sm:inline">Paste</span></button>
        </div>
        <button type="submit" data-submit class="btn-primary h-12 w-full sm:w-auto sm:min-w-40">
            <i data-lucide="download" class="h-4 w-4"></i><span data-label>{{ $buttonLabel }}</span>
        </button>
    </form>

    <div data-alert role="alert" class="mt-4 hidden items-start gap-3 rounded-2xl border border-red-200 bg-red-50 p-4 text-left text-red-800 [&:not(.hidden)]:flex"></div>

    <div data-progress class="mt-6 hidden items-center justify-center gap-3 text-sm text-gray-600 [&:not(.hidden)]:flex" role="status" aria-live="polite">
        <i data-lucide="loader-circle" class="h-5 w-5 animate-spin text-brand-500"></i> Processing your media...
    </div>

    <div data-result aria-live="polite"></div>
</div>
