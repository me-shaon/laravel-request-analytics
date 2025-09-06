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
    ],

    'queue' => [
        'enabled' => env('REQUEST_ANALYTICS_QUEUE_ENABLED', false),
    ],

    'ignore-paths' => [

    ],

    'database' => [
        'connection' => env('REQUEST_ANALYTICS_DB_CONNECTION', null), // null uses default Laravel connection
        'table' => env('REQUEST_ANALYTICS_DB_TABLE', 'request_analytics'),
    
    'pruning' => [
        'enabled' => env('REQUEST_ANALYTICS_PRUNING_ENABLED', true),
        'days' => env('REQUEST_ANALYTICS_PRUNING_DAYS', 90),
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

## Database Configuration

The package allows you to customize the database connection and table name used for storing analytics data. This is useful for:

- Using a separate database for analytics data
- Custom table naming conventions
- Multi-tenant applications
- Performance optimization by isolating analytics data

### Configuration Options

You can configure the database settings via environment variables or directly in the config file:

**Environment Variables (.env):**
```env
# Use a custom database connection (optional)
REQUEST_ANALYTICS_DB_CONNECTION=analytics_db

# Use a custom table name (optional)
REQUEST_ANALYTICS_DB_TABLE=site_analytics
```

**Config File (`config/request-analytics.php`):**
```php
'database' => [
    'connection' => env('REQUEST_ANALYTICS_DB_CONNECTION', null), // null uses default Laravel connection
    'table' => env('REQUEST_ANALYTICS_DB_TABLE', 'request_analytics'),
],
```

### Usage Examples

**Default Setup:**
- Uses your default Laravel database connection
- Creates `request_analytics` table
- No additional configuration needed

**Separate Database Connection:**
1. Define a new connection in `config/database.php`:
```php
'connections' => [
    // ... existing connections
    
    'analytics_db' => [
        'driver' => 'mysql',
        'host' => env('ANALYTICS_DB_HOST', '127.0.0.1'),
        'port' => env('ANALYTICS_DB_PORT', '3306'),
        'database' => env('ANALYTICS_DB_DATABASE', 'analytics'),
        'username' => env('ANALYTICS_DB_USERNAME', 'root'),
        'password' => env('ANALYTICS_DB_PASSWORD', ''),
        // ... other connection settings
    ],
],
```

2. Set the connection in your `.env`:
```env
REQUEST_ANALYTICS_DB_CONNECTION=analytics_db
ANALYTICS_DB_HOST=your-analytics-db-host
ANALYTICS_DB_DATABASE=analytics_database
ANALYTICS_DB_USERNAME=analytics_user
ANALYTICS_DB_PASSWORD=your-password
```

**Custom Table Name:**
```env
REQUEST_ANALYTICS_DB_TABLE=custom_analytics
```

**Both Custom Connection and Table:**
```env
REQUEST_ANALYTICS_DB_CONNECTION=analytics_db
REQUEST_ANALYTICS_DB_TABLE=site_tracking_data
```

### Migration

When you run the migration, it will automatically use your configured database connection and table name:

```bash
php artisan migrate
```

The migration will create the table with your specified name on your specified database connection.

## Usage

```php
$requestAnalytics = new MeShaon\RequestAnalytics();
echo $requestAnalytics->echoPhrase('Hello, MeShaon!');
```
## Access Control

### Web Access
To control access to the dashboard, implement the `CanAccessAnalyticsDashboard` interface in your User model:
Then you can use the `canAccessAnalyticsDashboard` method in your your `User` model:
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
