<?php

use MeShaon\RequestAnalytics\Models\RequestAnalytics;
use Illuminate\Support\Facades\Config;

describe('Configurable Table Name', function () {
    beforeEach(function () {
        // Reset configuration before each test
        Config::set('request-analytics.database.table', 'request_analytics');
        Config::set('request-analytics.database.connection', null);
    });

    it('uses default table name when no configuration is set', function () {
        $model = new RequestAnalytics();
        expect($model->getTable())->toBe('request_analytics');
    });

    it('uses configured table name from config', function () {
        Config::set('request-analytics.database.table', 'custom_analytics');
        
        $model = new RequestAnalytics();
        expect($model->getTable())->toBe('custom_analytics');
    });

    it('uses table name from environment variable simulation', function () {
        Config::set('request-analytics.database.table', 'env_analytics_table');
        
        $model = new RequestAnalytics();
        expect($model->getTable())->toBe('env_analytics_table');
    });

    it('handles special characters in table name', function () {
        Config::set('request-analytics.database.table', 'analytics_2024_v1');
        
        $model = new RequestAnalytics();
        expect($model->getTable())->toBe('analytics_2024_v1');
    });

    it('table name is applied on every new instance', function () {
        Config::set('request-analytics.database.table', 'first_table');
        $model1 = new RequestAnalytics();
        
        Config::set('request-analytics.database.table', 'second_table');
        $model2 = new RequestAnalytics();
        
        expect($model1->getTable())->toBe('first_table');
        expect($model2->getTable())->toBe('second_table');
    });

    it('falls back to default when config returns null', function () {
        Config::set('request-analytics.database.table', null);
        
        $model = new RequestAnalytics();
        expect($model->getTable())->toBe('request_analytics');
    });

    it('handles empty string configuration', function () {
        Config::set('request-analytics.database.table', '');
        
        $model = new RequestAnalytics();
        expect($model->getTable())->toBe('');
    });

    it('preserves table name across multiple instances with same config', function () {
        Config::set('request-analytics.database.table', 'shared_table');
        
        $model1 = new RequestAnalytics();
        $model2 = new RequestAnalytics();
        
        expect($model1->getTable())->toBe('shared_table');
        expect($model2->getTable())->toBe('shared_table');
        expect($model1->getTable())->toEqual($model2->getTable());
    });

    it('can handle long table names', function () {
        $longTableName = 'very_long_analytics_table_name_for_testing_purposes_2024';
        Config::set('request-analytics.database.table', $longTableName);
        
        $model = new RequestAnalytics();
        expect($model->getTable())->toBe($longTableName);
    });

    it('table name configuration is read from config helper', function () {
        Config::set('request-analytics.database.table', 'config_test_table');
        
        expect(config('request-analytics.database.table'))->toBe('config_test_table');
        
        $model = new RequestAnalytics();
        expect($model->getTable())->toBe('config_test_table');
    });
});