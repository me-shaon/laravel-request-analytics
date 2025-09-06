<?php

return [
    'route' => [
        'name' => 'request.analytics',
        'pathname' => env('REQUEST_ANALYTICS_PATHNAME', 'analytics'),
    ],

    'capture' => [
        'web' => true,
        'api' => true,
    ],

    'queue' => [
        'enabled' => env('REQUEST_ANALYTICS_QUEUE_ENABLED', false),
    ],

    'ignore-paths' => [

    ],

    'database' => [
        'connection' => env('REQUEST_ANALYTICS_DB_CONNECTION', null), // null uses default Laravel connection
        'table' => env('REQUEST_ANALYTICS_DB_TABLE', 'request_analytics'),
    ],
];
