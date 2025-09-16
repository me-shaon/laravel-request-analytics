<?php

namespace MeShaon\RequestAnalytics;

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Console\Scheduling\Schedule;
use MeShaon\RequestAnalytics\Commands\RequestAnalyticsCommand;
use MeShaon\RequestAnalytics\Http\Middleware\AnalyticsDashboardMiddleware;
use MeShaon\RequestAnalytics\Http\Middleware\APIRequestCapture;
use MeShaon\RequestAnalytics\Http\Middleware\WebRequestCapture;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class RequestAnalyticsServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $this->publishes([
            __DIR__.'/../resources/assets' => public_path('/'),
        ], 'assets');

        $this->publishes([
            __DIR__.'/../config/request-analytics.php' => config_path('request-analytics.php'),
        ], 'config');

        $package
            ->name('laravel-request-analytics')
            ->hasConfigFile()
            ->hasViews()
            ->hasRoute('web')
            ->hasRoute('api')
            ->hasAssets()
            ->hasMigration('create_request_analytics_table')
            ->hasCommand(RequestAnalyticsCommand::class);

        $this->registerMiddlewareAsAliases();
    }

    public function boot(): void
    {
        parent::boot();
        $this->pushMiddlewareToPipeline();

        // Conditionally register pruning schedule so users need no manual setup
        $this->callAfterResolving(Schedule::class, function (Schedule $schedule): void {
            if (config('request-analytics.pruning.enabled', true)) {
                $schedule->command('model:prune', [
                    '--model' => 'MeShaon\\RequestAnalytics\\Models\\RequestAnalytics',
                ])->daily();
            }
        });
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
