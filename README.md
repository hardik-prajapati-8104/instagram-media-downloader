# Instagram Media Downloader (Laravel 13)

Overlay of application files for a **fresh Laravel 13 project**. Theme: orange (#f97316) + "Lexend Deca".
It only works with an **authorized media provider/API you are allowed to use**. With no provider configured, the UI says so and refuses requests (no fake results).

> Status: written without access to Composer/PHP in the authoring sandbox, so it has NOT been executed. Run `php artisan test` first and fix any version-specific differences (see Troubleshooting).

## 1. Install
```bash
composer create-project laravel/laravel igdl        # Laravel 13 (PHP 8.3+, check laravel.com/docs/13.x)
cd igdl
# copy this overlay over the project (overwrites bootstrap/app.php, routes/*, AppServiceProvider, vite.config.js, resources/css|js)
rm public/robots.txt                                 # served dynamically by a route
cp .env.example .env && php artisan key:generate     # then edit .env (DB, provider, FFmpeg)
npm install alpinejs lucide
npm install                                          # tailwindcss v4 + @tailwindcss/vite ship with the default skeleton
php artisan migrate                                  # includes jobs/cache/session tables from the skeleton
php artisan media:admin you@example.com --name="Your Name"   # prompts for password (min 12)
php artisan storage:link                             # optional: media itself stays private
npm run build
php artisan optimize
```
PHP extensions: curl, fileinfo, zip, mbstring, pdo_mysql, openssl.

## 2. Provider setup
`.env`:
```
INSTAGRAM_MEDIA_PROVIDER=http
INSTAGRAM_PROVIDER_API_URL=https://api.your-provider.example/v1/media
INSTAGRAM_PROVIDER_API_KEY=...
INSTAGRAM_PROVIDER_METHOD=GET          # or POST (JSON body)
INSTAGRAM_PROVIDER_URL_PARAM=url       # name of the parameter carrying the Instagram URL
INSTAGRAM_PROVIDER_AUTH_HEADER=Authorization
INSTAGRAM_PROVIDER_AUTH_SCHEME=Bearer  # empty => raw key (e.g. header X-API-Key)
MEDIA_ALLOWED_HOSTS=cdn.your-provider.example   # extra hosts media may be fetched from
```
Expected reply (keys are tolerant, see `ProviderResponseNormalizer`):
`{"success":true,"title":"..","thumbnail":"https://..","items":[{"type":"video","url":"https://..","mime_type":"video/mp4","width":1080,"height":1920,"duration":15}]}`
Everything is https-only; media hosts must be on the allow-list (cdninstagram.com, fbcdn.net, the provider host, `MEDIA_ALLOWED_HOSTS`). For a different API shape add an adapter implementing `MediaProviderInterface` and register it in `config/instagram.php`.
Admin → Settings can override provider/URL/key (encrypted), timeout, limits, retention, queue name, FFmpeg paths.

## 3. Queue & scheduler
```bash
php artisan queue:work --queue=default --tries=3 --timeout=300     # use Supervisor in production
php artisan schedule:work                                           # dev; production cron below
* * * * * cd /path/to/igdl && php artisan schedule:run >> /dev/null 2>&1
```
Supervisor: `command=php /path/igdl/artisan queue:work --sleep=1 --tries=3 --timeout=300 --max-time=3600`, `numprocs=2`, `autorestart=true`. Restart workers on deploy: `php artisan queue:restart`. Use `QUEUE_CONNECTION=sync` only for local debugging.

## 4. FFmpeg
`sudo apt install ffmpeg` then set `FFMPEG_BINARY=/usr/bin/ffmpeg`, `FFPROBE_BINARY=/usr/bin/ffprobe`. Needs libmp3lame (default in Ubuntu builds). Check: `ffmpeg -encoders | grep mp3lame`.

## 5. Commands
`php artisan media:cleanup [--dry-run]`, `php artisan media:stats --days=7`, `php artisan media:admin {email}`.

## 6. Tests
`php artisan test` — provider, media hosts, DNS and FFmpeg are all mocked/faked; nothing touches Instagram.

## 7. Production checklist
- `APP_ENV=production APP_DEBUG=false`, HTTPS, `$middleware->trustProxies(at: '*')` in `bootstrap/app.php` if behind a proxy (required for signed URLs).
- MySQL 8+, `CACHE_STORE=database|redis` (cache locks are used), `SESSION_DRIVER=database`.
- `MEDIA_DISK` must be a private *local* disk. Never expose `storage/app/private`.
- Web server: deny dotfiles, set `client_max_body_size` small (no uploads exist).
- Run queue workers under Supervisor and the scheduler under cron.
- Add real Privacy/Terms text reviewed by a lawyer; set `CONTACT_EMAIL` + mail driver.

## 8. Troubleshooting
| Symptom | Fix |
|---|---|
| Banner "Media processing is currently unavailable" | Provider name/URL/key missing (.env or Admin → Settings). |
| Stuck on "Processing" | Worker not running / wrong queue name. `media:cleanup` fails stuck jobs after 30 min. |
| `ffmpeg_unavailable` | Wrong binary path or not installed. |
| 403 on download links | URL expired/tampered, or proxy not trusted so https/host mismatch invalidates the signature. |
| `unsafe_media_url` | Media host not allow-listed: add to `MEDIA_ALLOWED_HOSTS`. |
| 419 on POST | Session/cookie domain mismatch; reload the page. |
| Icons missing | Icon names are in `resources/js/icons.js`; add any new icon there. |
| Lucide import error | Names differ by lucide version; rename in `icons.js` (e.g. `CircleAlert`/`AlertCircle`). |

## Legal
Use only for content you own or have permission to save. Bypassing private-account restrictions, DRM or rate limits is not implemented and must not be added. Check your provider's and the platform's terms.
