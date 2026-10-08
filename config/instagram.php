<?php

use App\Services\Instagram\InstagramProviderAdapter;

return [
    // Driver name. Leave empty = "not configured" (the UI then refuses to pretend it works).
    'provider' => env('INSTAGRAM_MEDIA_PROVIDER', 'http'),

    // Registered provider drivers: name => adapter class.
    'providers' => [
        'http' => InstagramProviderAdapter::class,
    ],

    'provider_api_url' => env('INSTAGRAM_PROVIDER_API_URL', 'https://api.saveapi.org/v1/download'),
    'provider_api_key' => env('INSTAGRAM_PROVIDER_API_KEY'),
    'provider_timeout' => (int) env('INSTAGRAM_PROVIDER_TIMEOUT', 30),
    'provider_method' => strtoupper(env('INSTAGRAM_PROVIDER_METHOD', 'GET')),   // GET | POST
    'provider_url_param' => env('INSTAGRAM_PROVIDER_URL_PARAM', 'url'),
    'provider_auth_header' => env('INSTAGRAM_PROVIDER_AUTH_HEADER', 'Authorization'),
    'provider_auth_scheme' => env('INSTAGRAM_PROVIDER_AUTH_SCHEME', 'Bearer'),  // empty => raw key (e.g. X-API-Key)

    'allowed_domains' => ['instagram.com', 'www.instagram.com', 'm.instagram.com'],

    'cache_ttl' => (int) env('INSTAGRAM_CACHE_TTL', 600),

    // User-facing messages. Technical details are only ever logged server-side.
    'errors' => [
        'invalid_url' => 'Please paste a valid Instagram link (https://www.instagram.com/...).',
        'unsupported_url' => 'This link is not supported. Use a post, reel or video link such as instagram.com/p/..., /reel/... or /tv/...',
        'provider_not_configured' => 'Media processing is currently unavailable. Please configure a supported media provider.',
        'provider_unavailable' => 'Unable to process this Instagram link right now. Please verify that the content is publicly accessible and try again.',
        'provider_timeout' => 'The media service took too long to respond. Please try again in a moment.',
        'provider_rate_limited' => 'The media service is busy right now. Please try again shortly.',
        'provider_invalid_response' => 'Unable to process this Instagram link right now. Please verify that the content is publicly accessible and try again.',
        'empty_response' => 'No media could be found for this link.',
        'missing_media' => 'No downloadable media was found for this link.',
        'unsupported_media' => 'This type of media is not supported.',
        'private_content' => 'This content is private or requires a login, so it cannot be processed.',
        'deleted_content' => 'This content was deleted or is no longer available.',
        'expired_media' => 'The media link has expired. Please submit the link again.',
        'expired_result' => 'This result has expired. Please submit the link again.',
        'unsafe_media_url' => 'The media source was rejected for security reasons.',
        'download_failed' => 'The media file could not be retrieved. Please try again.',
        'file_too_large' => 'This file is larger than the maximum allowed size.',
        'invalid_file' => 'The retrieved file is not a supported media type.',
        'no_audio' => 'This video does not contain an accessible audio stream.',
        'ffmpeg_unavailable' => 'Audio conversion is currently unavailable on this server.',
        'audio_failed' => 'Audio extraction failed. Please try again later.',
        'stale_request' => 'This request took too long and was cancelled. Please try again.',
        'rate_limit' => 'Too many requests. Please wait a moment and try again.',
        'processing_failed' => 'Processing failed. Please try again.',
    ],
];
