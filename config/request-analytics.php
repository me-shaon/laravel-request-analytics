<?php

return [
    'database' => [
        'connection' => env('REQUEST_ANALYTICS_DB_CONNECTION', null), // Use default connection if null
        'table' => env('REQUEST_ANALYTICS_TABLE_NAME', 'request_analytics'),
    ],

    'route' => [
        'name' => 'request.analytics',
        'pathname' => env('REQUEST_ANALYTICS_PATHNAME', 'analytics'),
    ],

    'capture' => [
        'web' => true,
        'api' => true,
        'bots' => false, // Set to true to capture bot traffic
    ],

    'middleware' => [
        'web' => [
            'web',
            // 'auth', // Uncomment if using web authentication
            'request-analytics.access',
        ],
        'api' => [
            'api',
            // 'auth:sanctum', // Uncomment if using Sanctum authentication
            'request-analytics.access',
        ],
    ],

    'queue' => [
        'enabled' => env('REQUEST_ANALYTICS_QUEUE_ENABLED', false),
    ],

    'ignore-paths' => [
        env('REQUEST_ANALYTICS_PATHNAME', 'analytics'),
        'broadcasting/auth',
        'livewire/*',
    ],

    'pruning' => [
        'enabled' => env('REQUEST_ANALYTICS_PRUNING_ENABLED', true),
        'days' => env('REQUEST_ANALYTICS_PRUNING_DAYS', 90),
    ],

    'geolocation' => [
        'enabled' => env('REQUEST_ANALYTICS_GEO_ENABLED', true),
        'provider' => env('REQUEST_ANALYTICS_GEO_PROVIDER', 'ipapi'), // ipapi, ipgeolocation, maxmind
        'api_key' => env('REQUEST_ANALYTICS_GEO_API_KEY'),

        // MaxMind specific configuration
        'maxmind' => [
            'type' => env('REQUEST_ANALYTICS_MAXMIND_TYPE', 'webservice'), // webservice or database
            'user_id' => env('REQUEST_ANALYTICS_MAXMIND_USER_ID'),
            'license_key' => env('REQUEST_ANALYTICS_MAXMIND_LICENSE_KEY'),
            'database_path' => env('REQUEST_ANALYTICS_MAXMIND_DB_PATH', storage_path('app/GeoLite2-City.mmdb')),
        ],
    ],

    'privacy' => [
        'anonymize_ip' => env('REQUEST_ANALYTICS_ANONYMIZE_IP', false),
        'respect_dnt' => env('REQUEST_ANALYTICS_RESPECT_DNT', true), // Respect Do Not Track header
    ],

    'cache' => [
        'ttl' => env('REQUEST_ANALYTICS_CACHE_TTL', 5), // Cache TTL in minutes
    ],

];
