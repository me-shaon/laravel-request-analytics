<?php

namespace MeShaon\RequestAnalytics;

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;
use MeShaon\RequestAnalytics\Commands\RequestAnalyticsCommand;
use MeShaon\RequestAnalytics\Commands\PublishRequestAnalyticsAssetsCommand;
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
            ->hasRoutes([
                'web',
                'api',
            ])
            ->hasAssets()
            ->hasMigration('create_request_analytics_table')
            ->hasCommands([
                RequestAnalyticsCommand::class,
                PublishRequestAnalyticsAssetsCommand::class,
            ]);

        $this->registerMiddlewareAsAliases();
    }

    public function boot(): void
    {
        parent::boot();
        $this->pushMiddlewareToPipeline();
        $this->cleanupAndRepublishAssets();
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

    /**
     * Clean up old published assets and views, then republish them.
     * This runs when the package version has changed.
     */
    private function cleanupAndRepublishAssets(): void
    {
        // Skip if auto-publishing is explicitly disabled
        if (config('request-analytics.auto_publish_on_update') === false) {
            return;
        }

        // Check if package version has changed
        if (!$this->hasPackageVersionChanged()) {
            return;
        }

        try {
            $currentVersion = $this->getCurrentPackageVersion();
            
            if (config('request-analytics.publishing.log_publishing_activity', true)) {
                if (function_exists('logger')) {
                    logger()->info('Request Analytics: Starting auto-republish process', [
                        'version' => $currentVersion,
                    ]);
                }
            }

            // Paths to clean up
            $vendorViewsPath = resource_path('views/vendor/request-analytics');
            $vendorAssetsPath = public_path('vendor/request-analytics');

            // Clean up old published files if enabled
            if (config('request-analytics.publishing.cleanup_before_publish', true)) {
                if (File::exists($vendorViewsPath)) {
                    File::deleteDirectory($vendorViewsPath);
                    if (config('request-analytics.publishing.log_publishing_activity', true) && function_exists('logger')) {
                        logger()->info('Request Analytics: Cleaned up old views', ['path' => $vendorViewsPath]);
                    }
                }

                if (File::exists($vendorAssetsPath)) {
                    File::deleteDirectory($vendorAssetsPath);
                    if (config('request-analytics.publishing.log_publishing_activity', true) && function_exists('logger')) {
                        logger()->info('Request Analytics: Cleaned up old assets', ['path' => $vendorAssetsPath]);
                    }
                }
            }

            $forcePublish = config('request-analytics.publishing.force_publish', true);

            // Republish views
            Artisan::call('vendor:publish', [
                '--tag' => 'request-analytics-views',
                '--force' => $forcePublish,
            ]);

            // Republish assets
            Artisan::call('vendor:publish', [
                '--tag' => 'request-analytics-assets',
                '--force' => $forcePublish,
            ]);

            // Update the stored version
            $this->updateStoredPackageVersion();

            if (config('request-analytics.publishing.log_publishing_activity', true)) {
                if (function_exists('logger')) {
                    logger()->info('Request Analytics: Successfully completed auto-republish process', [
                        'version' => $currentVersion,
                        'views_path' => $vendorViewsPath,
                        'assets_path' => $vendorAssetsPath,
                    ]);
                }
            }

        } catch (\Exception $e) {
            // Log the error but don't break the application
            if (function_exists('logger')) {
                logger()->warning('Request Analytics: Failed to auto-republish assets', [
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]);
            }
        }
    }

    /**
     * Check if the package version has changed since last run.
     */
    private function hasPackageVersionChanged(): bool
    {
        $currentVersion = $this->getCurrentPackageVersion();
        $storedVersion = $this->getStoredPackageVersion();

        return $currentVersion !== $storedVersion;
    }

    /**
     * Get the current package version from composer.json.
     */
    private function getCurrentPackageVersion(): string
    {
        $composerJsonPath = __DIR__ . '/../composer.json';
        
        if (!File::exists($composerJsonPath)) {
            return 'unknown';
        }

        $composerData = json_decode(File::get($composerJsonPath), true);
        
        return $composerData['version'] ?? 'dev-main';
    }

    /**
     * Get the stored package version from cache/storage.
     */
    private function getStoredPackageVersion(): ?string
    {
        $versionFile = storage_path('framework/cache/request-analytics-version');
        
        if (!File::exists($versionFile)) {
            return null;
        }

        return File::get($versionFile);
    }

    /**
     * Update the stored package version.
     */
    private function updateStoredPackageVersion(): void
    {
        $currentVersion = $this->getCurrentPackageVersion();
        $versionFile = storage_path('framework/cache/request-analytics-version');
        
        // Ensure the directory exists
        $directory = dirname($versionFile);
        if (!File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        File::put($versionFile, $currentVersion);
    }
}
