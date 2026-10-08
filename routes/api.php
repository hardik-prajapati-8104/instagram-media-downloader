<?php

use App\Http\Controllers\DownloadController;
use App\Http\Controllers\MediaDownloadController;
use Illuminate\Support\Facades\Route;

// Routes that create work use the "web" group so sessions + CSRF protection apply (the page sends X-CSRF-TOKEN).
Route::middleware('web')->group(function () {
    Route::post('download', [DownloadController::class, 'store'])->middleware('throttle:download')->name('api.download.store');
    Route::post('download/{uuid}/retry', [DownloadController::class, 'retry'])->whereUuid('uuid')->middleware('throttle:download-retry')->name('api.download.retry');
});

Route::middleware('throttle:download-status')->group(function () {
    Route::get('download/{uuid}/status', [DownloadController::class, 'status'])->whereUuid('uuid')->name('api.download.status');
    Route::get('download/{uuid}/result', [DownloadController::class, 'result'])->whereUuid('uuid')->name('api.download.result');
});

// Temporary signed URLs only: tamper-proof and expiring.
Route::middleware(['signed', 'throttle:download-status'])->group(function () {
    Route::get('media/{id}/download', [MediaDownloadController::class, 'download'])->whereNumber('id')->name('media.download');
    Route::get('media/{id}/preview', [MediaDownloadController::class, 'preview'])->whereNumber('id')->name('media.preview');
});
