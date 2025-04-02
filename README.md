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
php artisan vendor:publish --tag="laravel-request-analytics-migrations"
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
        'enabled' => env('REQUEST_ANALYTICS_QUEUE_ENABLED', true),
    ],

    'ignore-paths' => [

    ],
    
    'pruning' => [
        'enabled' => env('REQUEST_ANALYTICS_PRUNING_ENABLED', true),
        'days' => env('REQUEST_ANALYTICS_PRUNING_DAYS', 90),
    ],
];
```
### Data Purning 
You can delete your data from your database automatically.
Add this to your `routes/console.php`

```php
use Illuminate\Support\Facades\Schedule;
 
Schedule::command('model:prune')->everyMinute();
```
Or If you are using Laravel 11+ then you may use this also into your `bootstarp/app.php`
```php
use Illuminate\Console\Scheduling\Schedule;
->withSchedule(function (Schedule $schedule) {
     $schedule->command('model:prune')->everyMinute();
    })
```
You can publish the assets with this command:
```bash
php artisan vendor:publish --tag="request-analytics-assets"
```

Optionally, you can publish the views using

```bash
php artisan vendor:publish --tag="request-analytics-views"
```

## Usage

```php
$requestAnalytics = new MeShaon\RequestAnalytics();
echo $requestAnalytics->echoPhrase('Hello, MeShaon!');
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
