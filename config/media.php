<?php

return [
    // Must be a LOCAL-driver disk (files are streamed to disk and processed by FFmpeg). Keep it private.
    'disk' => env('MEDIA_DISK', 'local'),
    'directory' => 'media',

    'retention_minutes' => (int) env('MEDIA_RETENTION_MINUTES', 60),
    'max_file_size' => (int) env('MEDIA_MAX_FILE_SIZE', 157286400), // bytes (150 MB)
    'max_carousel_items' => 20,
    'stale_minutes' => 30,
    'log_retention_days' => 30,

    // Hosts (and their sub-domains) the app may download media from. The configured provider host is added automatically.
    'allowed_media_hosts' => array_values(array_filter(array_merge(
        ['cdninstagram.com', 'fbcdn.net'],
        array_map('trim', explode(',', (string) env('MEDIA_ALLOWED_HOSTS', '')))
    ))),

    // Detected MIME => extension. The extension ALWAYS comes from here, never from a URL or provider file name.
    'allowed_mimes' => [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'video/mp4' => 'mp4',
        'video/x-m4v' => 'mp4',
        'video/quicktime' => 'mov',
        'video/webm' => 'webm',
        'audio/mpeg' => 'mp3',
        'audio/mp4' => 'm4a',
        'application/zip' => 'zip',
    ],

    'ffmpeg' => env('FFMPEG_BINARY', 'ffmpeg'),
    'ffprobe' => env('FFPROBE_BINARY', 'ffprobe'),
    'ffmpeg_timeout' => (int) env('FFMPEG_TIMEOUT', 120),

    'rate' => [
        'limit' => (int) env('DOWNLOAD_RATE_LIMIT', 10),
        'window' => (int) env('DOWNLOAD_RATE_WINDOW', 1), // minutes
        'status_per_minute' => 120,
        'retry_per_minute' => 5,
    ],

    'queue' => env('MEDIA_QUEUE', 'default'),
    'contact_email' => env('CONTACT_EMAIL'),
];
