<?php

declare(strict_types=1);

namespace MeShaon\RequestAnalytics\Tests\Feature;

use Illuminate\Support\Facades\File;
use MeShaon\RequestAnalytics\Tests\Feature\BaseFeatureTestCase;
use PHPUnit\Framework\Attributes\Test;

class AutoPublishAssetsTest extends BaseFeatureTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Clean up any existing published files
        $this->cleanupPublishedFiles();
    }

    protected function tearDown(): void
    {
        // Clean up after tests
        $this->cleanupPublishedFiles();
        $this->cleanupVersionFile();
        
        parent::tearDown();
    }

    #[Test]
    public function it_does_not_auto_publish_when_disabled(): void
    {
        // Disable auto-publishing
        config(['request-analytics.auto_publish_on_update' => false]);

        $vendorViewsPath = resource_path('views/vendor/request-analytics');
        $vendorAssetsPath = public_path('vendor/request-analytics');

        // Ensure directories don't exist initially
        $this->assertFalse(File::exists($vendorViewsPath));
        $this->assertFalse(File::exists($vendorAssetsPath));

        // Trigger the service provider boot process
        $this->app->register(\MeShaon\RequestAnalytics\RequestAnalyticsServiceProvider::class);
        $this->app->boot();

        // Assets should not be auto-published
        $this->assertFalse(File::exists($vendorViewsPath));
        $this->assertFalse(File::exists($vendorAssetsPath));
    }

    #[Test]
    public function it_auto_publishes_when_version_changes(): void
    {
        // Enable auto-publishing
        config(['request-analytics.auto_publish_on_update' => true]);

        $vendorViewsPath = resource_path('views/vendor/request-analytics');
        $vendorAssetsPath = public_path('vendor/request-analytics');

        // Ensure no version file exists (simulating first run or version change)
        $this->cleanupVersionFile();

        // Manually call the cleanup and republish method using reflection
        $serviceProvider = new \MeShaon\RequestAnalytics\RequestAnalyticsServiceProvider($this->app);
        $serviceProvider->configurePackage(new \Spatie\LaravelPackageTools\Package('laravel-request-analytics'));
        $reflection = new \ReflectionClass($serviceProvider);
        $method = $reflection->getMethod('cleanupAndRepublishAssets');
        $method->setAccessible(true);
        $method->invoke($serviceProvider);

        // Assets should be auto-published
        $this->assertTrue(File::exists($vendorViewsPath));
        $this->assertTrue(File::exists($vendorAssetsPath));
        $this->assertTrue(File::exists($vendorViewsPath . '/analytics.blade.php'));
        $this->assertTrue(File::exists($vendorAssetsPath . '/browsers/chrome.png'));
    }

    #[Test]
    public function it_does_not_republish_when_version_unchanged(): void
    {
        // Enable auto-publishing
        config(['request-analytics.auto_publish_on_update' => true]);

        $vendorViewsPath = resource_path('views/vendor/request-analytics');
        $vendorAssetsPath = public_path('vendor/request-analytics');

        // Simulate that assets were already published
        File::makeDirectory($vendorViewsPath, 0755, true);
        File::makeDirectory($vendorAssetsPath, 0755, true);
        File::put($vendorViewsPath . '/test.blade.php', 'existing view');
        File::put($vendorAssetsPath . '/test.css', 'existing css');

        // Set the current version as already stored
        $this->setStoredVersion($this->getCurrentVersion());

        // Manually call the cleanup and republish method using reflection
        $serviceProvider = new \MeShaon\RequestAnalytics\RequestAnalyticsServiceProvider($this->app);
        $serviceProvider->configurePackage(new \Spatie\LaravelPackageTools\Package('laravel-request-analytics'));
        $reflection = new \ReflectionClass($serviceProvider);
        $method = $reflection->getMethod('cleanupAndRepublishAssets');
        $method->setAccessible(true);
        $method->invoke($serviceProvider);

        // Original test files should still exist (not cleaned up)
        $this->assertTrue(File::exists($vendorViewsPath . '/test.blade.php'));
        $this->assertTrue(File::exists($vendorAssetsPath . '/test.css'));
    }

    #[Test]
    public function it_cleans_up_before_publishing_when_configured(): void
    {
        // Enable auto-publishing and cleanup
        config([
            'request-analytics.auto_publish_on_update' => true,
            'request-analytics.publishing.cleanup_before_publish' => true,
        ]);

        $vendorViewsPath = resource_path('views/vendor/request-analytics');
        $vendorAssetsPath = public_path('vendor/request-analytics');

        // Create existing files
        File::makeDirectory($vendorViewsPath, 0755, true);
        File::makeDirectory($vendorAssetsPath, 0755, true);
        File::put($vendorViewsPath . '/old.blade.php', 'old view');
        File::put($vendorAssetsPath . '/old.css', 'old css');

        // Ensure no version file exists (simulating version change)
        $this->cleanupVersionFile();

        // Manually call the cleanup and republish method using reflection
        $serviceProvider = new \MeShaon\RequestAnalytics\RequestAnalyticsServiceProvider($this->app);
        $serviceProvider->configurePackage(new \Spatie\LaravelPackageTools\Package('laravel-request-analytics'));
        $reflection = new \ReflectionClass($serviceProvider);
        $method = $reflection->getMethod('cleanupAndRepublishAssets');
        $method->setAccessible(true);
        $method->invoke($serviceProvider);

        // Old files should be cleaned up
        $this->assertFalse(File::exists($vendorViewsPath . '/old.blade.php'));
        $this->assertFalse(File::exists($vendorAssetsPath . '/old.css'));

        // New files should be published
        $this->assertTrue(File::exists($vendorViewsPath . '/analytics.blade.php'));
        $this->assertTrue(File::exists($vendorAssetsPath . '/browsers/chrome.png'));
    }

    #[Test]
    public function it_does_not_cleanup_when_disabled(): void
    {
        // Enable auto-publishing but disable cleanup
        config([
            'request-analytics.auto_publish_on_update' => true,
            'request-analytics.publishing.cleanup_before_publish' => false,
        ]);

        $vendorViewsPath = resource_path('views/vendor/request-analytics');
        $vendorAssetsPath = public_path('vendor/request-analytics');

        // Create existing files
        File::makeDirectory($vendorViewsPath, 0755, true);
        File::makeDirectory($vendorAssetsPath, 0755, true);
        File::put($vendorViewsPath . '/old.blade.php', 'old view');
        File::put($vendorAssetsPath . '/old.css', 'old css');

        // Ensure no version file exists (simulating version change)
        $this->cleanupVersionFile();

        // Manually call the cleanup and republish method using reflection
        $serviceProvider = new \MeShaon\RequestAnalytics\RequestAnalyticsServiceProvider($this->app);
        $serviceProvider->configurePackage(new \Spatie\LaravelPackageTools\Package('laravel-request-analytics'));
        $reflection = new \ReflectionClass($serviceProvider);
        $method = $reflection->getMethod('cleanupAndRepublishAssets');
        $method->setAccessible(true);
        $method->invoke($serviceProvider);

        // Old files should still exist
        $this->assertTrue(File::exists($vendorViewsPath . '/old.blade.php'));
        $this->assertTrue(File::exists($vendorAssetsPath . '/old.css'));

        // New files should also be published
        $this->assertTrue(File::exists($vendorViewsPath . '/analytics.blade.php'));
        $this->assertTrue(File::exists($vendorAssetsPath . '/browsers/chrome.png'));
    }

    private function cleanupPublishedFiles(): void
    {
        $vendorViewsPath = resource_path('views/vendor/request-analytics');
        $vendorAssetsPath = public_path('vendor/request-analytics');

        if (File::exists($vendorViewsPath)) {
            File::deleteDirectory($vendorViewsPath);
        }

        if (File::exists($vendorAssetsPath)) {
            File::deleteDirectory($vendorAssetsPath);
        }
    }

    private function cleanupVersionFile(): void
    {
        $versionFile = storage_path('framework/cache/request-analytics-version');
        
        if (File::exists($versionFile)) {
            File::delete($versionFile);
        }
    }

    private function getCurrentVersion(): string
    {
        $composerJsonPath = __DIR__ . '/../../composer.json';
        
        if (!File::exists($composerJsonPath)) {
            return 'unknown';
        }

        $composerData = json_decode(File::get($composerJsonPath), true);
        
        return $composerData['version'] ?? 'dev-main';
    }

    private function setStoredVersion(string $version): void
    {
        $versionFile = storage_path('framework/cache/request-analytics-version');
        
        // Ensure the directory exists
        $directory = dirname($versionFile);
        if (!File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        File::put($versionFile, $version);
    }
}
