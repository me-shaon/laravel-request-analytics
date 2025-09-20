<?php

namespace MeShaon\RequestAnalytics;

use Illuminate\Contracts\Http\Kernel;
use MeShaon\RequestAnalytics\Commands\RequestAnalyticsCommand;
use MeShaon\RequestAnalytics\Commands\SetupCommand;
use MeShaon\RequestAnalytics\Http\Middleware\AnalyticsDashboardMiddleware;
use MeShaon\RequestAnalytics\Http\Middleware\APIRequestCapture;
use MeShaon\RequestAnalytics\Http\Middleware\WebRequestCapture;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class RequestAnalyticsServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        // Manually publish migrations with proper timestamp
        $this->publishes([
            __DIR__.'/../database/migrations/create_request_analytics_table.php' => database_path('migrations/'.date('Y_m_d_His').'_create_request_analytics_table.php'),
        ], 'laravel-request-analytics-migrations');

        // Manually publish config with correct tag
        $this->publishes([
            __DIR__.'/../config/request-analytics.php' => config_path('request-analytics.php'),
        ], 'laravel-request-analytics-config');

        // Manually publish assets with correct tag
        $this->publishes([
            __DIR__.'/../resources/assets' => public_path('/'),
        ], 'laravel-request-analytics-assets');

        $package
            ->name('laravel-request-analytics')
            ->hasViews()
            ->hasRoute('web')
            ->hasRoute('api')
            ->hasCommand(RequestAnalyticsCommand::class);

        $this->registerMiddlewareAsAliases();
    }

    public function boot(): void
    {
        parent::boot();
        $this->pushMiddlewareToPipeline();

        if ($this->app->runningInConsole()) {
            $this->commands([
                SetupCommand::class,
            ]);
        }
    }

    private function registerMiddlewareAsAliases(): void
    {
        /* @var \Illuminate\Routing\Router */
        $router = $this->app->make('router');

        $router->aliasMiddleware('request-analytics.web', WebRequestCapture::class);
        $router->aliasMiddleware('request-analytics.api', APIRequestCapture::class);
        $router->aliasMiddleware('request-analytics.access', AnalyticsDashboardMiddleware::class);
    }

    private function pushMiddlewareToPipeline(): void
    {
        if (config('request-analytics.capture.web')) {
            $this->app[Kernel::class]->appendMiddlewareToGroup('web', WebRequestCapture::class);
        }

        if (config('request-analytics.capture.api')) {
            $this->app[Kernel::class]->appendMiddlewareToGroup('api', APIRequestCapture::class);
        }
    }
}
