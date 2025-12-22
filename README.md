# Laravel Request Analytics

[![Latest Version on Packagist](https://img.shields.io/packagist/v/me-shaon/laravel-request-analytics.svg?style=flat-square)](https://packagist.org/packages/me-shaon/laravel-request-analytics)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/me-shaon/laravel-request-analytics/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/me-shaon/laravel-request-analytics/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/me-shaon/laravel-request-analytics/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/me-shaon/laravel-request-analytics/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/me-shaon/laravel-request-analytics.svg?style=flat-square)](https://packagist.org/packages/me-shaon/laravel-request-analytics)

![Laravel request analytics](https://github.com/me-shaon/laravel-request-analytics/blob/main/banner_preview.png?raw=true)

## Overview

Laravel Request Analytics is a comprehensive web analytics solution designed specifically for Laravel applications. This package provides detailed insights into your application's traffic patterns, user behavior, and performance metrics through an intuitive dashboard and powerful API endpoints.

Built with performance and privacy in mind, the package offers intelligent bot detection, IP geolocation services, and GDPR-compliant data handling. Whether you're running a small blog or a large-scale application, Laravel Request Analytics provides the tools you need to understand your audience and optimize user experience.


## Installation

### Requirements

- PHP 8.1 or higher
- Laravel 10.0 or higher
- MySQL 5.7+ / PostgreSQL 10+ / SQLite 3.8+

### Quick Installation (Recommended) ✨

Get up and running in just two commands:

```bash
# Install the package
composer require me-shaon/laravel-request-analytics

# Run the interactive installer
php artisan request-analytics:install
```

This interactive installer will:
- Welcome you with helpful information about the package
- Automatically publish configuration files, assets, and views
- Publish and optionally run database migrations
- Optionally prompt you to star our GitHub repository
- Provide next steps and dashboard access information

After running the installer, your analytics dashboard will be ready at `/analytics`!

[![Package Installation Guideline Video Banner](https://github.com/me-shaon/laravel-request-analytics/blob/main/package-installation-guideline-video-banner.webp?raw=true)](https://youtu.be/m3m6t2v0YR8)

### Manual Setup (Advanced)

For users who prefer manual control over the installation process or need custom configurations:

#### Database Setup

Publish and run the migrations:

```bash
php artisan vendor:publish --tag="request-analytics-migrations"
php artisan migrate
```

#### Configuration

Publish the configuration file:

```bash
php artisan vendor:publish --tag="request-analytics-config"
```

The configuration file will be published to `config/request-analytics.php` with the following options:

```php
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
        'on_queue' => env('REQUEST_ANALYTICS_ON_QUEUE', 'default'),
    ],

    'ignore-paths' => [
        env('REQUEST_ANALYTICS_PATHNAME', 'analytics'),
        'broadcasting/auth',
        'livewire/*',
    ],

    'skip_ips' => [
        // '127.0.0.1',
        // '192.168.1.0/24',
        // Add IP addresses or CIDR blocks to skip tracking
    ],

    'skip_referrers' => [
        // 'spam-site.com',
        // 'unwanted-referrer.com',
        // Add referrer domains to skip tracking
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
    ],

    'cache' => [
        'ttl' => env('REQUEST_ANALYTICS_CACHE_TTL', 5), // Cache TTL in minutes
    ],
];
```
#### Publish Assets & Views

Publish dashboard assets:
```bash
php artisan vendor:publish --tag="request-analytics-assets"
```

Optionally, publish the views for customization:
```bash
php artisan vendor:publish --tag="request-analytics-views"
```

#### Publishing Options

The package supports granular publishing of individual components:

```bash
# Publish specific components
php artisan vendor:publish --tag="request-analytics-config"
php artisan vendor:publish --tag="request-analytics-migrations" 
php artisan vendor:publish --tag="request-analytics-assets"
php artisan vendor:publish --tag="request-analytics-views"

# Or publish everything at once
php artisan vendor:publish --provider="MeShaon\RequestAnalytics\RequestAnalyticsServiceProvider"
```

### Automated Data Pruning

The package includes automatic data cleanup to manage database size. Configure pruning in your scheduler:

**Laravel 11+**

Add to `routes/console.php`:
```php
use Illuminate\Support\Facades\Schedule;

Schedule::command('model:prune', [
    '--model' => 'MeShaon\RequestAnalytics\Models\RequestAnalytics',
])->monthly();
```

Or in `bootstrap/app.php`:
```php
use Illuminate\Console\Scheduling\Schedule;

->withSchedule(function (Schedule $schedule) {
    $schedule->command('model:prune', [
        '--model' => 'MeShaon\RequestAnalytics\Models\RequestAnalytics',
    ])->monthly();
})
```

**Laravel 10 and below**

Add to `app/Console/Kernel.php`:
```php
protected function schedule(Schedule $schedule): void
{
    $schedule->command('model:prune', [
        '--model' => 'MeShaon\RequestAnalytics\Models\RequestAnalytics',
    ])->monthly();
}
```

## Key Features

### Analytics & Reporting
- **Real-time Dashboard**: Interactive charts and metrics with responsive design
- **Comprehensive Metrics**: Page views, unique visitors, bounce rates, and session duration
- **Traffic Analysis**: Detailed breakdown of traffic sources and user pathways
- **Performance Insights**: Load times and user interaction patterns

### Privacy & Compliance
- **GDPR Compliance**: Built-in privacy controls and data anonymization
- **IP Anonymization**: Configurable IP address masking for user privacy
- **Data Retention**: Configurable automatic data pruning and cleanup

### Intelligence & Detection
- **Advanced Bot Detection**: Filters search engines, social bots, and crawlers
- **IP Address Filtering**: Skip tracking for specific IP addresses or CIDR blocks (e.g., internal IPs, admin IPs)
- **Referrer Filtering**: Exclude tracking for requests from specific referrer domains (e.g., spam sites, unwanted sources)
- **Device Recognition**: Browser, OS, and device type identification
- **Geolocation Services**: Multiple provider support (IP-API, IPGeolocation, MaxMind)
- **Visitor Tracking**: Cookie-based unique visitor identification

### Performance & Integration
- **High Performance**: Optimized database queries with intelligent caching
- **Queue Support**: Background processing for high-traffic applications
- **REST API**: Complete programmatic access to analytics data
- **Laravel Integration**: Seamless integration with Laravel's authentication and middleware systems

## Usage

### Getting Started

After installation, the package automatically starts tracking requests based on your configuration. Here's what happens next:

1. **Dashboard Access**: Visit `/analytics` (or your configured pathname) to view the analytics dashboard
2. **Automatic Tracking**: Request data is captured automatically for web and API routes

### Quick Commands

```bash
# Run the interactive installer
php artisan request-analytics:install

# View available analytics commands
php artisan list | grep analytics

# Clear old analytics data (if pruning is enabled)
php artisan model:prune --model="MeShaon\RequestAnalytics\Models\RequestAnalytics"
```

## Configuration Options

### Route Configuration
- `route.name`: Named route identifier (default: `request.analytics`)
- `route.pathname`: URL path for dashboard access (default: `analytics`)

### Data Capture Settings
- `capture.web`: Track web requests (default: `true`)
- `capture.api`: Track API requests (default: `true`)
- `capture.bots`: Include bot traffic in analytics (default: `false`)

### Queue Processing
- `queue.enabled`: Process analytics data in background jobs for better performance
- `queue.on_queue`: Specifies the queue name for processing analytics jobs

### Path Filtering
- `ignore-paths`: Array of paths to exclude from tracking (e.g., admin routes, health checks)

### IP and Referrer Filtering
Filter out unwanted traffic to improve data quality and protect privacy by excluding specific IP addresses and referrer domains from analytics tracking.

- `skip_ips`: Array of IP addresses or CIDR blocks to skip tracking. Supports exact IP matches (e.g., `'127.0.0.1'`) and CIDR notation for IP ranges (e.g., `'192.168.1.0/24'`). Useful for excluding internal networks, admin IPs, or development environments.
- `skip_referrers`: Array of referrer domains to exclude from tracking. Filters out spam referrers, bot traffic, or internal tools (e.g., `'spam-site.com'`, `'internal-tool.com'`) to maintain clean analytics data.

### Data Retention
- `pruning.enabled`: Automatic data cleanup (default: `true`)
- `pruning.days`: Days to retain data (default: 90)

### Geolocation Services

The package supports multiple geolocation providers:

#### IP-API (Default - Free)
```php
'geolocation' => [
    'enabled' => true,
    'provider' => 'ipapi',
    'api_key' => null, // Not required
]
```
- No API key required
- 45 requests per minute limit
- Includes country, region, city, timezone

#### IPGeolocation
```php
'geolocation' => [
    'enabled' => true,
    'provider' => 'ipgeolocation',
    'api_key' => env('REQUEST_ANALYTICS_GEO_API_KEY'),
]
```
- Requires API key from [ipgeolocation.io](https://ipgeolocation.io)
- Higher rate limits and accuracy
- Additional ISP and threat intelligence data

#### MaxMind
```php
'geolocation' => [
    'enabled' => true,
    'provider' => 'maxmind',
    'api_key' => env('REQUEST_ANALYTICS_GEO_API_KEY'),
]
```
- Requires GeoIP2 database or web service account
- Highest accuracy and performance
- Enterprise-grade IP intelligence

### Privacy & Compliance
```php
'privacy' => [
    'anonymize_ip' => env('REQUEST_ANALYTICS_ANONYMIZE_IP', false),
]
```

- **IP Anonymization**: Masks the last octet of IPv4 addresses (192.168.1.xxx)

### Bot Detection

Advanced bot detection includes:
- **Search Engines**: Google, Bing, Yahoo, DuckDuckGo, Baidu
- **Social Media**: Facebook, Twitter, LinkedIn, Pinterest crawlers
- **SEO Tools**: Ahrefs, SEMrush, Moz, Screaming Frog
- **Monitoring**: Pingdom, UptimeRobot, StatusCake
- **Development**: curl, wget, Postman, Insomnia

## Dashboard Access & Usage

### Dashboard Access
The analytics dashboard is available at `/analytics` by default. Access the dashboard through your configured route after authentication.

### Access Control
Implement the `CanAccessAnalyticsDashboard` interface in your User model to control dashboard access:

```php
<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use MeShaon\RequestAnalytics\Contracts\CanAccessAnalyticsDashboard;

class User extends Authenticatable implements CanAccessAnalyticsDashboard
{
    public function canAccessAnalyticsDashboard(): bool
    {
        // Example: Only allow admin users
        return $this->role === 'admin';

        // Or check specific permissions
        // return $this->can('view-analytics');

        // Or allow all authenticated users
        // return true;
    }
}
```

### Dashboard Features
- **Real-time Metrics**: Live visitor count, page views, and bounce rate
- **Interactive Charts**: Traffic trends, geographic distribution, device breakdown
- **Top Pages**: Most visited pages with performance metrics
- **Visitor Insights**: Browser, OS, and device analytics
- **Traffic Sources**: Referrer analysis and search engine traffic
- **Performance Data**: Page load times and user engagement metrics

## API Documentation

The package provides a comprehensive REST API for programmatic access to analytics data.

### Endpoints

#### GET /api/v1/analytics/overview
Retrieve comprehensive analytics overview with summary statistics and chart data.

**Parameters:**
- `date_range` (optional): Number of days to look back (1-365, default: 30)
- `start_date` (optional): Start date (YYYY-MM-DD format)
- `end_date` (optional): End date (YYYY-MM-DD format, must be after start_date)
- `with_percentages` (optional): Include percentage data for applicable fields (true/false)

**Response:**
```json
{
    "data": {
        "summary": {
            "views": 15420,
            "visitors": 8760,
            "bounce_rate": "65.4%",
            "average_visit_time": "3m 2s"
        },
        "chart": {
            "labels": ["Jan 01", "Jan 02", "Jan 03"],
            "datasets": [
                {"label": "Views", "data": [120, 150, 180]},
                {"label": "Visitors", "data": [85, 95, 110]}
            ]
        },
        "top_pages": [
            {"path": "/home", "views": 1250},
            {"path": "/products", "views": 890}
        ],
        "top_referrers": [
            {"domain": "google.com", "visits": 450},
            {"domain": "facebook.com", "visits": 280}
        ],
        "browsers": [
            {"browser": "Chrome", "count": 5420},
            {"browser": "Firefox", "count": 2180}
        ],
        "devices": [
            {"name": "Desktop", "count": 8760, "percentage": 65.2},
            {"name": "Mobile", "count": 4680, "percentage": 34.8}
        ],
        "countries": [
            {"name": "United States", "count": 5420, "percentage": 40.2, "code": "us"},
            {"name": "Canada", "count": 2180, "percentage": 16.1, "code": "ca"}
        ],
        "operating_systems": [
            {"name": "Windows", "count": 6840, "percentage": 50.8},
            {"name": "macOS", "count": 3920, "percentage": 29.1}
        ]
    },
    "date_range": {
        "start": "2024-01-01T00:00:00.000000Z",
        "end": "2024-01-31T23:59:59.000000Z",
        "days": 30,
        "key": "2024-01-01_2024-01-31"
    }
}
```

#### GET /api/v1/analytics/visitors
Get paginated visitor data with detailed information.

**Parameters:**
- `date_range` (optional): Number of days to look back (1-365, default: 30)
- `page` (optional): Page number for pagination (default: 1)
- `per_page` (optional): Items per page, max 100 (default: 50)

**Response:**
```json
{
    "data": {
        "current_page": 1,
        "data": [
            {
                "visitor_id": "abc123xyz",
                "page_views": 15,
                "sessions": 3,
                "first_visit": "2024-01-10T10:30:00.000000Z",
                "last_visit": "2024-01-15T14:30:00.000000Z",
                "unique_pages": 8
            }
        ],
        "total": 8760,
        "per_page": 50,
        "last_page": 176
    }
}
```

#### GET /api/v1/analytics/page-views
Retrieve paginated page view data with performance metrics.

**Parameters:**
- `date_range` (optional): Number of days to look back (1-365, default: 30)
- `path` (optional): Filter by specific path pattern (partial match)
- `page` (optional): Page number for pagination (default: 1)
- `per_page` (optional): Items per page, max 100 (default: 50)

**Response:**
```json
{
    "data": {
        "current_page": 1,
        "data": [
            {
                "id": 1,
                "path": "/products/smartphone",
                "page_title": "Latest Smartphones",
                "ip_address": "192.168.1.***",
                "operating_system": "Windows 10",
                "browser": "Chrome",
                "device": "Desktop",
                "screen": "1920x1080",
                "referrer": "https://google.com",
                "country": "US",
                "city": "New York",
                "language": "en-US",
                "query_params": "{\"utm_source\":\"google\"}",
                "session_id": "abc123xyz",
                "visitor_id": "def456uvw",
                "user_id": 42,
                "http_method": "GET",
                "request_category": "web",
                "response_time": 1250,
                "visited_at": "2024-01-15T14:30:00.000000Z"
            }
        ],
        "total": 15420,
        "per_page": 50
    }
}
```

### Error Handling
API responses follow consistent error format:

```json
{
    "success": false,
    "message": "Validation failed",
    "errors": {
        "period": ["The selected period is invalid."]
    }
}
```

**Common HTTP Status Codes:**
- `200`: Success
- `400`: Bad Request (validation errors)
- `401`: Unauthorized (invalid or missing token)
- `403`: Forbidden (insufficient permissions)
- `429`: Too Many Requests (rate limited)
- `500`: Internal Server Error

## Testing

```bash
vendor/bin/phpunit --no-coverage
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Ahmed shamim](https://github.com/me-shaon)
- [Omar Faruque](https://github.com/OmarFaruk-0x01)
- [Md Abul Hassan](https://github.com/theihasan)
- [Al Nahian](https://github.com/alnahian2003)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
