<?php

namespace MeShaon\RequestAnalytics\Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
use MeShaon\RequestAnalytics\RequestAnalyticsServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'MeShaon\\RequestAnalytics\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );
    }

    protected function getPackageProviders($app)
    {
        return [
            RequestAnalyticsServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        // Set up test database configuration
        config()->set('database.default', 'testing');
        config()->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        // Set up secondary test connection for connection tests
        config()->set('database.connections.analytics_test', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        // Set up test session configuration
        config()->set('session.driver', 'array');

        // Set up auth configuration for testing
        config()->set('auth.defaults.provider', 'users');
        config()->set('auth.providers.users.driver', 'eloquent');
        config()->set('auth.providers.users.model', \Illuminate\Foundation\Auth\User::class);
    }
}
