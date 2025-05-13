<?php

use Illuminate\Support\Facades\Route;
use MeShaon\RequestAnalytics\Controllers\RequestAnalyticsController;

Route::middleware(['web', 'auth', 'request-analytics.access'])
    ->get(config('request-analytics.route.pathname'), [RequestAnalyticsController::class, 'show'])
    ->name(config('request-analytics.route.name'));

Route::middleware(['api', 'auth:sanctum', 'request-analytics.access'])
    ->get('api/v1'.config('request-analytics.route.pathname'), function () {
        return response()->json([
            'data' => 'Coming soon...',
        ]);
    })->name(config('request-analytics.route.name').'.api');
