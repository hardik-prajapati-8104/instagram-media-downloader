<?php

use App\Http\Controllers\Backend;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\PageController;
use Illuminate\Support\Facades\Route;

$pages = [
    // uri => [seo key, view, route name]
    '/' => ['home', 'home', 'home'],
    '/video' => ['video', 'video', 'video'],
    '/reels' => ['reels', 'reels', 'reels'],
    '/photo' => ['photo', 'photo', 'photo'],
    '/carousel' => ['carousel', 'carousel', 'carousel'],
    '/audio' => ['audio', 'audio', 'audio'],
    '/how-to-download' => ['how-to', 'how-to-download', 'how-to'],
    '/faq' => ['faq', 'faq', 'faq'],
    '/privacy-policy' => ['privacy', 'privacy-policy', 'privacy'],
    '/terms' => ['terms', 'terms', 'terms'],
    '/contact' => ['contact', 'contact', 'contact'],
];

foreach ($pages as $uri => [$key, $view, $name]) {
    Route::get($uri, [PageController::class, 'show'])->defaults('page', $key)->defaults('view', $view)->name($name);
}

Route::post('/contact', [ContactController::class, 'send'])->middleware('throttle:5,1')->name('contact.send');
Route::get('/sitemap.xml', [PageController::class, 'sitemap'])->name('sitemap');
Route::get('/robots.txt', [PageController::class, 'robots'])->name('robots');

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('login', [Backend\AuthController::class, 'showLogin'])->name('login');
    Route::post('login', [Backend\AuthController::class, 'login'])->name('login.submit');

    Route::middleware('admin.auth')->group(function () {
        Route::redirect('/', '/admin/dashboard');
        Route::post('logout', [Backend\AuthController::class, 'logout'])->name('logout');
        Route::get('dashboard', [Backend\DashboardController::class, 'index'])->name('dashboard');
        Route::get('downloads', [Backend\DownloadsController::class, 'index'])->name('downloads.index');
        Route::get('downloads/{uuid}', [Backend\DownloadsController::class, 'show'])->whereUuid('uuid')->name('downloads.show');
        Route::get('logs', [Backend\LogsController::class, 'index'])->name('logs');
        Route::get('statistics', [Backend\StatisticsController::class, 'index'])->name('statistics');
        Route::get('settings', [Backend\SettingsController::class, 'index'])->name('settings');
        Route::put('settings', [Backend\SettingsController::class, 'update'])->name('settings.update');
    });
});
