# Laravel Request Analytics

[![Latest Version on Packagist](https://img.shields.io/packagist/v/me-shaon/laravel-request-analytics.svg?style=flat-square)](https://packagist.org/packages/me-shaon/laravel-request-analytics)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/me-shaon/laravel-request-analytics/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/me-shaon/laravel-request-analytics/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/me-shaon/laravel-request-analytics/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/me-shaon/laravel-request-analytics/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/me-shaon/laravel-request-analytics.svg?style=flat-square)](https://packagist.org/packages/me-shaon/laravel-request-analytics)

<h3 align="center">Simple request data analytics package for Laravel projects.</h3>

![Laravel request analytics](https://github.com/me-shaon/laravel-request-analytics/blob/main/preview.png?raw=true)


## Installation

You can install the package via Composer:

```bash
composer require me-shaon/laravel-request-analytics
```

You can publish and run the migrations with:

```bash
php artisan vendor:publish --tag="request-analytics-migrations"
php artisan migrate
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="request-analytics-config"
```

This is the contents of the published config file:

```php
return [
    'route' => [
        'name' => 'request.analytics',
        'pathname' => env('REQUEST_ANALYTICS_PATHNAME', 'analytics'),
    ],

    'capture' => [
        'web' => true,
        'api' => true,
        'bots' => false, // Set to true to capture bot traffic
    ],

    'queue' => [
        'enabled' => env('REQUEST_ANALYTICS_QUEUE_ENABLED', false),
    ],

    'ignore-paths' => [
        // Add paths to ignore, e.g., 'admin', 'api/health'
    ],
    
    'pruning' => [
        'enabled' => env('REQUEST_ANALYTICS_PRUNING_ENABLED', true),
        'days' => env('REQUEST_ANALYTICS_PRUNING_DAYS', 90),
    ],

    'geolocation' => [
        'enabled' => env('REQUEST_ANALYTICS_GEO_ENABLED', true),
        'provider' => env('REQUEST_ANALYTICS_GEO_PROVIDER', 'ipapi'), // ipapi, ipgeolocation, maxmind
        'api_key' => env('REQUEST_ANALYTICS_GEO_API_KEY'),
    ],

    'privacy' => [
        'anonymize_ip' => env('REQUEST_ANALYTICS_ANONYMIZE_IP', false),
        'respect_dnt' => env('REQUEST_ANALYTICS_RESPECT_DNT', true), // Respect Do Not Track header
    ],
];
```
### Data Purning 
You can delete your data from your database automatically.

If you are using Laravel 11+ then you may use `model:prune` command.
Add this to your `routes/console.php`

```php
use Illuminate\Support\Facades\Schedule;
 
Schedule::command('model:prune', [
            '--model' => 'MeShaon\RequestAnalytics\Models\RequestAnalytics',
        ])->daily();
``` 
Or try this `bootstarp/app.php`
```php
use Illuminate\Console\Scheduling\Schedule;
->withSchedule(function (Schedule $schedule) {
     $schedule->command('model:prune', [
            '--model' => 'MeShaon\RequestAnalytics\Models\RequestAnalytics',
        ])->daily();
    })
```

If you are using Laravel 10 or below then you may use `model:prune` command.
You may define all of your scheduled tasks in the schedule method of your application's `App\Console\Kernel` class
```php
<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('model:prune', [
            '--model' => 'MeShaon\RequestAnalytics\Models\RequestAnalytics',
        ])->daily();
    }
}
```

You can publish the assets with this command:
```bash
php artisan vendor:publish --tag="request-analytics-assets"
```

Optionally, you can publish the views using

```bash
php artisan vendor:publish --tag="request-analytics-views"
```

## Features

- 📊 **Real-time Analytics Dashboard** - Beautiful, responsive dashboard with charts and metrics
- 🤖 **Bot Detection** - Automatically filters out bot traffic (configurable)
- 🌍 **IP Geolocation** - Track visitor locations using multiple providers
- 🔒 **Privacy Focused** - GDPR compliant with IP anonymization and DNT support
- 🚀 **High Performance** - Built-in caching and optimized database queries
- 📱 **Device Detection** - Track browsers, operating systems, and devices
- 👥 **Visitor Tracking** - Unique visitor identification with cookie-based tracking
- 📤 **Export Data** - Export analytics data to CSV or JSON formats
- 🔌 **REST API** - Full-featured API for programmatic access
- 🧹 **Auto Cleanup** - Automatic data pruning to manage database size

## Usage

### Dashboard Access

After installation, the analytics dashboard is available at `/analytics` (configurable). Users must be authenticated and implement the `CanAccessAnalyticsDashboard` interface.
## API Endpoints

The package provides a comprehensive REST API for accessing analytics data:

- `GET /api/v1/analytics/overview` - Get analytics overview with summary and charts
- `GET /api/v1/analytics/visitors` - Get paginated visitor data
- `GET /api/v1/analytics/page-views` - Get paginated page view data
- `POST /api/v1/analytics/export` - Export analytics data to CSV or JSON

### API Authentication

API endpoints use Laravel Sanctum for authentication. Ensure your API consumers have valid tokens.

## Configuration Options

### Geolocation Providers

The package supports multiple geolocation providers:

1. **IP-API** (default, free): No API key required, limited to 45 requests per minute
2. **IPGeolocation**: Requires API key from [ipgeolocation.io](https://ipgeolocation.io)
3. **MaxMind**: Requires GeoIP2 database or web service account

### Privacy Settings

- **IP Anonymization**: Enable to anonymize the last octet of IPv4 addresses
- **Do Not Track**: Respect the DNT browser header (enabled by default)

### Bot Detection

The package automatically detects and filters common bots and crawlers including:
- Search engine bots (Google, Bing, Yahoo, etc.)
- Social media bots (Facebook, Twitter, LinkedIn, etc.)
- SEO tools (Ahrefs, SEMrush, etc.)
- Monitoring services (Pingdom, UptimeRobot, etc.)
- Development tools (curl, wget, Postman, etc.)

## Access Control

### Web Access
To control access to the dashboard, implement the `CanAccessAnalyticsDashboard` interface in your User model:
```php
<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\Role;
use MeShaon\RequestAnalytics\Contracts\CanAccessAnalyticsDashboard;

class User extends Authenticatable implements CanAccessAnalyticsDashboard
{
    
    public function canAccessAnalyticsDashboard(): bool
    {
        return $this->role === Role::ADMIN;
    }
}

```

## Testing

```bash
composer test
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
- [Md Abul Hassan](https://github.com/imabulhasan99)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
