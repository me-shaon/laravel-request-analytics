<?php

namespace MeShaon\RequestAnalytics\Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use MeShaon\RequestAnalytics\RequestAnalyticsServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName): string => 'MeShaon\\RequestAnalytics\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );

        // Ensure the table is created
        $this->createRequestAnalyticsTable();
    }

    protected function getPackageProviders($app)
    {
        return [
            RequestAnalyticsServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app): void
    {
        config()->set('database.default', 'testing');
        config()->set('app.key', 'base64:'.base64_encode(random_bytes(32)));

        config()->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        // Configure package for testing
        config()->set('request-analytics.enabled', true);
        config()->set('request-analytics.database.table', 'request_analytics');
        config()->set('request-analytics.route.name', 'request-analytics.dashboard');
        config()->set('request-analytics.route.pathname', '/analytics');
        config()->set('request-analytics.capture.web', true);
        config()->set('request-analytics.capture.api', true);
        config()->set('request-analytics.queue.enabled', false);
        config()->set('request-analytics.privacy.anonymize_ip', false);
        config()->set('request-analytics.ignore-paths', []);
        config()->set('request-analytics.geolocation.enabled', false);
        config()->set('request-analytics.data.prune_days', 365);

        // Configure authentication for testing
        config()->set('auth.defaults.guard', 'web');
        config()->set('auth.guards.web', [
            'driver' => 'session',
            'provider' => 'users',
        ]);
        config()->set('auth.guards.sanctum', [
            'driver' => 'sanctum',
            'provider' => null,
        ]);

        $migrationPath = __DIR__.'/../database/migrations/';
        $app['migrator']->path($migrationPath);
    }

    protected function createRequestAnalyticsTable(): void
    {
        if (! Schema::hasTable('request_analytics')) {
            Schema::create('request_analytics', function (Blueprint $table): void {
                $table->id();
                $table->string('path');
                $table->string('page_title')->nullable();
                $table->string('ip_address');
                $table->string('operating_system')->nullable();
                $table->string('browser')->nullable();
                $table->string('device')->nullable();
                $table->string('screen')->nullable();
                $table->string('referrer')->nullable();
                $table->string('country')->nullable();
                $table->string('city')->nullable();
                $table->string('language')->nullable();
                $table->text('query_params')->nullable();
                $table->string('session_id');
                $table->string('visitor_id')->nullable();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->string('http_method');
                $table->string('request_category');
                $table->bigInteger('response_time')->nullable();
                $table->timestamp('visited_at');
            });
        }
    }
}
