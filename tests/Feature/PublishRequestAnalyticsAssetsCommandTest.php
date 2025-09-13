<?php

declare(strict_types=1);

namespace MeShaon\RequestAnalytics\Tests\Feature;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;
use MeShaon\RequestAnalytics\Tests\Feature\BaseFeatureTestCase;
use PHPUnit\Framework\Attributes\Test;

class PublishRequestAnalyticsAssetsCommandTest extends BaseFeatureTestCase
{
    #[Test]
    public function it_can_publish_assets_and_views(): void
    {
        // Create dummy directories to simulate existing published files
        $vendorViewsPath = resource_path('views/vendor/request-analytics');
        $vendorAssetsPath = public_path('vendor/request-analytics');

        // Clean up first if directories exist
        if (File::exists($vendorViewsPath)) {
            File::deleteDirectory($vendorViewsPath);
        }
        if (File::exists($vendorAssetsPath)) {
            File::deleteDirectory($vendorAssetsPath);
        }

        File::makeDirectory($vendorViewsPath, 0755, true);
        File::makeDirectory($vendorAssetsPath, 0755, true);
        
        // Create dummy files
        File::put($vendorViewsPath . '/test.blade.php', 'test view');
        File::put($vendorAssetsPath . '/test.css', 'test css');

        // Ensure files exist
        $this->assertTrue(File::exists($vendorViewsPath . '/test.blade.php'));
        $this->assertTrue(File::exists($vendorAssetsPath . '/test.css'));

        // Run the command with clean option
        Artisan::call('request-analytics:publish', ['--clean' => true, '--force' => true]);

        // Check that old files were cleaned up
        $this->assertFalse(File::exists($vendorViewsPath . '/test.blade.php'));
        $this->assertFalse(File::exists($vendorAssetsPath . '/test.css'));

        // Check that new files were published
        $this->assertTrue(File::exists($vendorViewsPath));
        $this->assertTrue(File::exists($vendorAssetsPath));
        $this->assertTrue(File::exists($vendorViewsPath . '/analytics.blade.php'));
        $this->assertTrue(File::exists($vendorAssetsPath . '/browsers/chrome.png'));
    }

    #[Test]
    public function it_can_publish_without_cleaning(): void
    {
        // Disable cleanup in config
        config(['request-analytics.publishing.cleanup_before_publish' => false]);

        // Create dummy directories and files
        $vendorViewsPath = resource_path('views/vendor/request-analytics');
        $vendorAssetsPath = public_path('vendor/request-analytics');

        // Clean up first if directories exist
        if (File::exists($vendorViewsPath)) {
            File::deleteDirectory($vendorViewsPath);
        }
        if (File::exists($vendorAssetsPath)) {
            File::deleteDirectory($vendorAssetsPath);
        }

        File::makeDirectory($vendorViewsPath, 0755, true);
        File::makeDirectory($vendorAssetsPath, 0755, true);
        
        File::put($vendorViewsPath . '/test.blade.php', 'test view');
        File::put($vendorAssetsPath . '/test.css', 'test css');

        // Run the command without clean option
        Artisan::call('request-analytics:publish', ['--force' => true]);

        // Check that old files still exist
        $this->assertTrue(File::exists($vendorViewsPath . '/test.blade.php'));
        $this->assertTrue(File::exists($vendorAssetsPath . '/test.css'));

        // Check that new files were also published
        $this->assertTrue(File::exists($vendorViewsPath . '/analytics.blade.php'));
        $this->assertTrue(File::exists($vendorAssetsPath . '/browsers/chrome.png'));
    }

    #[Test]
    public function it_respects_configuration_for_cleanup_behavior(): void
    {
        // Set config to enable cleanup
        config(['request-analytics.publishing.cleanup_before_publish' => true]);

        $vendorViewsPath = resource_path('views/vendor/request-analytics');
        $vendorAssetsPath = public_path('vendor/request-analytics');

        // Clean up first if directories exist
        if (File::exists($vendorViewsPath)) {
            File::deleteDirectory($vendorViewsPath);
        }
        if (File::exists($vendorAssetsPath)) {
            File::deleteDirectory($vendorAssetsPath);
        }

        File::makeDirectory($vendorViewsPath, 0755, true);
        File::makeDirectory($vendorAssetsPath, 0755, true);
        
        File::put($vendorViewsPath . '/test.blade.php', 'test view');
        File::put($vendorAssetsPath . '/test.css', 'test css');

        // Run command without --clean flag but with config enabled
        Artisan::call('request-analytics:publish', ['--force' => true]);

        // Check that cleanup happened due to config
        $this->assertFalse(File::exists($vendorViewsPath . '/test.blade.php'));
        $this->assertFalse(File::exists($vendorAssetsPath . '/test.css'));

        // Check that new files were published
        $this->assertTrue(File::exists($vendorViewsPath . '/analytics.blade.php'));
        $this->assertTrue(File::exists($vendorAssetsPath . '/browsers/chrome.png'));
    }

    #[Test]
    public function it_respects_configuration_for_force_publish_behavior(): void
    {
        // Set config to enable force publish
        config(['request-analytics.publishing.force_publish' => true]);

        $vendorViewsPath = resource_path('views/vendor/request-analytics');
        $vendorAssetsPath = public_path('vendor/request-analytics');

        // Clean up first if directories exist
        if (File::exists($vendorViewsPath)) {
            File::deleteDirectory($vendorViewsPath);
        }
        if (File::exists($vendorAssetsPath)) {
            File::deleteDirectory($vendorAssetsPath);
        }

        File::makeDirectory($vendorViewsPath, 0755, true);
        File::makeDirectory($vendorAssetsPath, 0755, true);

        // Create existing files to test force behavior
        File::put($vendorViewsPath . '/analytics.blade.php', 'old view content');
        File::put($vendorAssetsPath . '/app.css', 'old css content');

        // Run command without --force flag but with config enabled
        Artisan::call('request-analytics:publish');

        // Files should still be updated due to config
        $this->assertTrue(File::exists($vendorViewsPath . '/analytics.blade.php'));
        $this->assertTrue(File::exists($vendorAssetsPath . '/browsers/chrome.png'));
        
        // Content should be updated (not the old content)
        $this->assertNotEquals('old view content', File::get($vendorViewsPath . '/analytics.blade.php'));
    }
}
