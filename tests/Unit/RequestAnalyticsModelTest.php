<?php

use MeShaon\RequestAnalytics\Models\RequestAnalytics;
use Illuminate\Support\Facades\Config;

describe('RequestAnalytics Model Configuration', function () {
    beforeEach(function () {
        // Reset configuration before each test
        Config::set('request-analytics.database.table', 'request_analytics');
        Config::set('request-analytics.database.connection', null);
    });

    it('applies both table and connection configuration together', function () {
        Config::set('request-analytics.database.table', 'custom_table');
        Config::set('request-analytics.database.connection', 'analytics_test');
        
        $model = new RequestAnalytics();
        
        expect($model->getTable())->toBe('custom_table');
        expect($model->getConnectionName())->toBe('analytics_test');
    });

    it('has correct model properties and constants', function () {
        $model = new RequestAnalytics();
        
        expect($model::UPDATED_AT)->toBeNull();
        expect($model::CREATED_AT)->toBeNull();
        expect($model->getGuarded())->toContain('id', 'created_at', 'updated_at');
    });

    it('can create instance with attributes', function () {
        Config::set('request-analytics.database.table', 'test_analytics');
        
        $attributes = [
            'path' => '/test',
            'ip_address' => '127.0.0.1',
            'session_id' => 'test_session'
        ];
        
        $model = new RequestAnalytics($attributes);
        
        expect($model->getTable())->toBe('test_analytics');
        expect($model->getAttribute('path'))->toBe('/test');
        expect($model->getAttribute('ip_address'))->toBe('127.0.0.1');
        expect($model->getAttribute('session_id'))->toBe('test_session');
    });

    it('inherits from Eloquent Model correctly', function () {
        $model = new RequestAnalytics();
        
        expect($model)->toBeInstanceOf(\Illuminate\Database\Eloquent\Model::class);
        expect(method_exists($model, 'getAttribute'))->toBeTrue();
        expect(method_exists($model, 'setAttribute'))->toBeTrue();
        expect(method_exists($model, 'getTable'))->toBeTrue();
        expect(method_exists($model, 'getConnectionName'))->toBeTrue();
    });

    it('has HasFactory trait', function () {
        $model = new RequestAnalytics();
        
        expect(class_uses($model))->toContain(\Illuminate\Database\Eloquent\Factories\HasFactory::class);
    });

    it('handles configuration changes without affecting existing instances', function () {
        Config::set('request-analytics.database.table', 'original_table');
        Config::set('request-analytics.database.connection', 'original_connection');
        
        $originalModel = new RequestAnalytics();
        
        // Change configuration
        Config::set('request-analytics.database.table', 'new_table');
        Config::set('request-analytics.database.connection', 'new_connection');
        
        $newModel = new RequestAnalytics();
        
        // Original model should keep its configuration
        expect($originalModel->getTable())->toBe('original_table');
        expect($originalModel->getConnectionName())->toBe('original_connection');
        
        // New model should use new configuration
        expect($newModel->getTable())->toBe('new_table');
        expect($newModel->getConnectionName())->toBe('new_connection');
    });

    it('constructor sets configuration in correct order', function () {
        Config::set('request-analytics.database.table', 'ordered_table');
        Config::set('request-analytics.database.connection', 'ordered_connection');
        
        $model = new RequestAnalytics([
            'path' => '/constructor-test'
        ]);
        
        // Configuration should be set
        expect($model->getTable())->toBe('ordered_table');
        expect($model->getConnectionName())->toBe('ordered_connection');
        
        // Attributes should also be set
        expect($model->getAttribute('path'))->toBe('/constructor-test');
    });

    it('handles null connection configuration properly', function () {
        Config::set('request-analytics.database.table', 'null_conn_table');
        Config::set('request-analytics.database.connection', null);
        
        $model = new RequestAnalytics();
        
        expect($model->getTable())->toBe('null_conn_table');
        expect($model->getConnectionName())->toBe(config('database.default'));
    });

    it('preserves model behavior with configuration', function () {
        Config::set('request-analytics.database.table', 'behavior_test');
        
        $attributes = [
            'path' => '/behavior',
            'page_title' => 'Test Page',
            'ip_address' => '192.168.1.1',
            'operating_system' => 'Linux',
            'browser' => 'Chrome',
            'device' => 'Desktop',
            'session_id' => 'session123',
            'http_method' => 'GET',
            'request_category' => 'web',
            'visited_at' => now()
        ];
        
        $model = new RequestAnalytics($attributes);
        
        // Should behave like normal Eloquent model
        expect($model->getTable())->toBe('behavior_test');
        expect($model->getAttribute('path'))->toBe('/behavior');
        expect($model->getAttribute('page_title'))->toBe('Test Page');
        expect($model->isDirty())->toBeTrue();
    });

    it('supports basic model functionality with configuration', function () {
        Config::set('request-analytics.database.table', 'functionality_test');
        
        $model = new RequestAnalytics();
        
        // Test basic Eloquent model functionality
        expect($model->getTable())->toBe('functionality_test');
        expect($model->getKeyName())->toBe('id');
        expect($model->getIncrementing())->toBeTrue();
        
        // Test that we can set and get attributes
        $model->path = '/test-path';
        expect($model->path)->toBe('/test-path');
    });

    it('handles edge case configurations', function () {
        // Test with unusual but valid configurations
        Config::set('request-analytics.database.table', '123_numeric_start');
        Config::set('request-analytics.database.connection', 'connection-with-dashes');
        
        $model = new RequestAnalytics();
        
        expect($model->getTable())->toBe('123_numeric_start');
        expect($model->getConnectionName())->toBe('connection-with-dashes');
    });
});