<?php

namespace MeShaon\RequestAnalytics;

use Illuminate\Contracts\Http\Kernel;
use MeShaon\RequestAnalytics\Http\Middleware\AnalyticsDashboardMiddleware;
use MeShaon\RequestAnalytics\Http\Middleware\APIRequestCapture;
use MeShaon\RequestAnalytics\Http\Middleware\WebRequestCapture;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class RequestAnalyticsServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-request-analytics')
            ->hasConfigFile()
            ->hasViews()
            ->hasRoutes(['web', 'api'])
            ->hasAssets()
            ->hasMigrations(['create_request_analytics_table', 'add_indexes_to_request_analytics_table'])
            ->hasInstallCommand(function (InstallCommand $command): void {
                $command
                    ->startWith(function (InstallCommand $command): void {
                        $command->info('Installing Laravel Request Analytics...');
                        $command->info('This package will help you track and analyze your application requests.');
                    })
                    ->publishConfigFile()
                    ->publishMigrations()
                    ->publishAssets()
                    ->askToRunMigrations()
                    ->endWith(function (InstallCommand $command): void {
                        $command->info('Laravel Request Analytics has been installed successfully!');
                        $command->info('You can now visit /analytics to view your dashboard.');
                        $command->info('Check the documentation for configuration options.');
                    })
                    ->askToStarRepoOnGitHub('me-shaon/laravel-request-analytics');
            });
    }

    public function packageRegistered(): void
    {
        $this->registerMiddlewareAsAliases();
    }

    public function packageBooted(): void
    {
        $this->pushMiddlewareToPipeline();
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
