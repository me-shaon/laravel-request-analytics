<?php

declare(strict_types=1);

namespace MeShaon\RequestAnalytics\Tests\Feature\Commands;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use MeShaon\RequestAnalytics\RequestAnalyticsServiceProvider;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\Test;

class SetupCommandTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [RequestAnalyticsServiceProvider::class];
    }

    #[Test]
    public function it_publishes_migrations_and_runs_only_package_migrations()
    {
        // Ensure clean state
        $migrationPath = database_path('migrations');
        File::cleanDirectory($migrationPath);

        $this->artisan('laravel-request-analytics:setup')
            ->expectsQuestion('How would you like to run migrations?',
                'Run only Request Analytics migrations (default)')
            ->assertExitCode(0);

        // Migration file should exist
        $files = glob($migrationPath.'/*_request_analytics*.php');
        $this->assertNotEmpty($files, 'Request Analytics migration file was not published.');

        // Migration should create the table
        $this->assertTrue(
            Schema::hasTable('request_analytics'),
            'The request_analytics table should exist after running setup.'
        );
    }

    #[Test]
    public function it_can_run_all_migrations_if_chosen()
    {
        $this->artisan('laravel-request-analytics:setup')
            ->expectsQuestion('How would you like to run migrations?', 'Run all migrations')
            ->assertExitCode(0);

        $this->assertTrue(
            Schema::hasTable('request_analytics'),
            'The request_analytics table should exist after running setup.'
        );
    }

    #[Test]
    public function it_publishes_config_and_assets()
    {
        $this->artisan('laravel-request-analytics:setup')
            ->expectsQuestion('How would you like to run migrations?',
                'Run only Request Analytics migrations (default)')
            ->assertExitCode(0);

        $this->assertFileExists(config_path('request-analytics.php'));

        $this->assertDirectoryExists(public_path('vendor/request-analytics'));
    }
}
