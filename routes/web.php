<?php

use Illuminate\Support\Facades\Route;
use MeShaon\RequestAnalytics\Controllers\Api\AnalyticsApiController;
use MeShaon\RequestAnalytics\Controllers\RequestAnalyticsController;

Route::middleware(['web', 'auth', 'request-analytics.access'])
    ->get(config('request-analytics.route.pathname'), [RequestAnalyticsController::class, 'show'])
    ->name(config('request-analytics.route.name'));

Route::middleware(['api', 'auth:sanctum', 'request-analytics.access'])
    ->prefix('api/v1/analytics')
    ->name('request-analytics.api.')
    ->group(function () {
        Route::get('/overview', [AnalyticsApiController::class, 'overview'])->name('overview');
        Route::get('/visitors', [AnalyticsApiController::class, 'visitors'])->name('visitors');
        Route::get('/page-views', [AnalyticsApiController::class, 'pageViews'])->name('page-views');
        Route::post('/export', [AnalyticsApiController::class, 'export'])->name('export');
    });
