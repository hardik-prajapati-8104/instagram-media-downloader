<?php

namespace App\Providers;

use App\Models\Admin;
use App\Services\Instagram\MediaProviderManager;
use App\Services\Instagram\SettingsService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SettingsService::class);

        // Separate "admin" guard registered here so config/auth.php stays untouched.
        config([
            'auth.guards.admin' => ['driver' => 'session', 'provider' => 'admins'],
            'auth.providers.admins' => ['driver' => 'eloquent', 'model' => Admin::class],
        ]);
    }

    public function boot(): void
    {
        Schema::class::defaultStringLength(191);
        URL::forceHttps($this->app->isProduction());
        JsonResource::withoutWrapping();

        $tooMany = fn (): JsonResponse => response()->json([
            'success' => false,
            'message' => config('instagram.errors.rate_limit'),
            'error' => ['code' => 'rate_limit'],
        ], 429);

        RateLimiter::for('download', function (Request $r) use ($tooMany) {
            $s = app(SettingsService::class);

            return Limit::perMinutes($s->rateWindow(), $s->rateLimit())->by('dl|' . $r->ip())->response($tooMany);
        });
        RateLimiter::for('download-status', fn (Request $r) => Limit::perMinute((int) config('media.rate.status_per_minute'))->by('st|' . $r->ip())->response($tooMany));
        RateLimiter::for('download-retry', fn (Request $r) => Limit::perMinute((int) config('media.rate.retry_per_minute'))->by('rt|' . $r->ip())->response($tooMany));

        View::composer('partials.downloader', function ($view) {
            $view->with('providerReady', app(MediaProviderManager::class)->isReady());
        });
    }
}
