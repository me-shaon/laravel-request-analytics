<?php

use MeShaon\RequestAnalytics\Models\RequestAnalytics;
use Illuminate\Support\Facades\Config;

describe('Configurable Database Connection', function () {
    beforeEach(function () {
        // Reset configuration before each test
        Config::set('request-analytics.database.connection', null);
        Config::set('request-analytics.database.table', 'request_analytics');
    });

    it('uses default connection when no configuration is set', function () {
        Config::set('request-analytics.database.connection', null);
        
        $model = new RequestAnalytics();
        expect($model->getConnectionName())->toBe(config('database.default'));
    });

    it('uses configured connection from config', function () {
        Config::set('request-analytics.database.connection', 'analytics_test');
        
        $model = new RequestAnalytics();
        expect($model->getConnectionName())->toBe('analytics_test');
    });

    it('uses null configuration to default to Laravel default connection', function () {
        Config::set('request-analytics.database.connection', null);
        
        $model = new RequestAnalytics();
        expect($model->getConnectionName())->toBe(config('database.default'));
    });

    it('handles empty string as fallback to default connection', function () {
        Config::set('request-analytics.database.connection', '');
        
        $model = new RequestAnalytics();
        // Empty string should not set connection, so it falls back to default
        expect($model->getConnectionName())->toBe(config('database.default'));
    });

    it('connection name is applied on every new instance', function () {
        Config::set('request-analytics.database.connection', 'analytics_test');
        $model1 = new RequestAnalytics();
        
        Config::set('request-analytics.database.connection', 'testing');
        $model2 = new RequestAnalytics();
        
        expect($model1->getConnectionName())->toBe('analytics_test');
        expect($model2->getConnectionName())->toBe('testing');
    });

    it('handles connection configuration changes dynamically', function () {
        // Start with no custom connection
        Config::set('request-analytics.database.connection', null);
        $model1 = new RequestAnalytics();
        
        // Change to custom connection
        Config::set('request-analytics.database.connection', 'analytics_test');
        $model2 = new RequestAnalytics();
        
        // Back to default
        Config::set('request-analytics.database.connection', null);
        $model3 = new RequestAnalytics();
        
        expect($model1->getConnectionName())->toBe(config('database.default'));
        expect($model2->getConnectionName())->toBe('analytics_test');
        expect($model3->getConnectionName())->toBe(config('database.default'));
    });

    it('preserves connection name across multiple instances with same config', function () {
        Config::set('request-analytics.database.connection', 'shared_connection');
        
        $model1 = new RequestAnalytics();
        $model2 = new RequestAnalytics();
        
        expect($model1->getConnectionName())->toBe('shared_connection');
        expect($model2->getConnectionName())->toBe('shared_connection');
        expect($model1->getConnectionName())->toEqual($model2->getConnectionName());
    });

    it('connection configuration is read from config helper', function () {
        Config::set('request-analytics.database.connection', 'config_test_connection');
        
        expect(config('request-analytics.database.connection'))->toBe('config_test_connection');
        
        $model = new RequestAnalytics();
        expect($model->getConnectionName())->toBe('config_test_connection');
    });

    it('handles special connection names', function () {
        $specialConnectionName = 'analytics-production-2024';
        Config::set('request-analytics.database.connection', $specialConnectionName);
        
        $model = new RequestAnalytics();
        expect($model->getConnectionName())->toBe($specialConnectionName);
    });

    it('can switch between different connection types', function () {
        // Test different connection names that might be used in real scenarios
        $connections = ['mysql_analytics', 'postgres_analytics', 'sqlite_analytics'];
        
        foreach ($connections as $connectionName) {
            Config::set('request-analytics.database.connection', $connectionName);
            $model = new RequestAnalytics();
            
            expect($model->getConnectionName())->toBe($connectionName);
        }
    });
});