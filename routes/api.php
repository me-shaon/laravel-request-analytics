<?php

use Illuminate\Support\Facades\Route;
use MeShaon\RequestAnalytics\Http\Controllers\Api\AnalyticsApiController;

Route::middleware(config('request-analytics.middleware.api'))
    ->prefix('api/v1/analytics')
    ->name('request-analytics.api.')
    ->group(function (): void {
        Route::get('/overview', [AnalyticsApiController::class, 'overview'])->name('overview');
        Route::get('/visitors', [AnalyticsApiController::class, 'visitors'])->name('visitors');
        Route::get('/page-views', [AnalyticsApiController::class, 'pageViews'])->name('page-views');
    });
