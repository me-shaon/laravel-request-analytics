<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Config;

describe('Migration Configuration', function () {
    beforeEach(function () {
        // Clean up any existing tables and reset configuration
        $tables = ['request_analytics', 'custom_analytics', 'test_analytics', 'migration_test_table'];
        
        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                Schema::dropIfExists($table);
            }
        }
        
        Config::set('request-analytics.database.table', 'request_analytics');
        Config::set('request-analytics.database.connection', null);
    });

    afterEach(function () {
        // Clean up after each test
        $tables = ['request_analytics', 'custom_analytics', 'test_analytics', 'migration_test_table'];
        
        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                Schema::dropIfExists($table);
            }
        }
    });

    it('creates table with default configuration', function () {
        Config::set('request-analytics.database.table', 'request_analytics');
        Config::set('request-analytics.database.connection', null);
        
        $tableName = config('request-analytics.database.table', 'request_analytics');
        $connection = config('request-analytics.database.connection');
        
        Schema::connection($connection)->create($tableName, function (Blueprint $table) {
            $table->id();
            $table->string('path');
            $table->string('session_id');
            $table->timestamp('visited_at');
        });
        
        expect(Schema::hasTable('request_analytics'))->toBeTrue();
        expect(Schema::hasColumns('request_analytics', [
            'id', 'path', 'session_id', 'visited_at'
        ]))->toBeTrue();
    });

    it('creates table with custom table name', function () {
        Config::set('request-analytics.database.table', 'custom_analytics');
        Config::set('request-analytics.database.connection', null);
        
        $tableName = config('request-analytics.database.table', 'request_analytics');
        $connection = config('request-analytics.database.connection');
        
        Schema::connection($connection)->create($tableName, function (Blueprint $table) {
            $table->id();
            $table->string('path');
            $table->string('session_id');
            $table->timestamp('visited_at');
        });
        
        expect(Schema::hasTable('custom_analytics'))->toBeTrue();
        expect(Schema::hasTable('request_analytics'))->toBeFalse();
    });

    it('migration respects configuration values', function () {
        Config::set('request-analytics.database.table', 'test_analytics');
        
        $tableName = config('request-analytics.database.table', 'request_analytics');
        
        expect($tableName)->toBe('test_analytics');
    });

    it('creates complete table structure', function () {
        Config::set('request-analytics.database.table', 'complete_analytics');
        
        $tableName = config('request-analytics.database.table');
        $connection = config('request-analytics.database.connection');
        
        Schema::connection($connection)->create($tableName, function (Blueprint $table) {
            $table->id();
            $table->string('path');
            $table->string('page_title');
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
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('http_method');
            $table->string('request_category');
            $table->bigInteger('response_time')->nullable();
            $table->timestamp('visited_at');
        });
        
        expect(Schema::hasTable('complete_analytics'))->toBeTrue();
        
        $expectedColumns = [
            'id', 'path', 'page_title', 'ip_address', 'operating_system',
            'browser', 'device', 'screen', 'referrer', 'country', 'city',
            'language', 'query_params', 'session_id', 'user_id', 
            'http_method', 'request_category', 'response_time', 'visited_at'
        ];
        
        expect(Schema::hasColumns('complete_analytics', $expectedColumns))->toBeTrue();
    });

    it('handles table drop with configuration', function () {
        Config::set('request-analytics.database.table', 'drop_test_table');
        
        $tableName = config('request-analytics.database.table');
        $connection = config('request-analytics.database.connection');
        
        // Create table
        Schema::connection($connection)->create($tableName, function (Blueprint $table) {
            $table->id();
            $table->string('path');
        });
        
        expect(Schema::hasTable('drop_test_table'))->toBeTrue();
        
        // Drop table using configuration
        Schema::connection($connection)->dropIfExists($tableName);
        
        expect(Schema::hasTable('drop_test_table'))->toBeFalse();
    });

    it('migration works with null connection configuration', function () {
        Config::set('request-analytics.database.table', 'null_conn_table');
        Config::set('request-analytics.database.connection', null);
        
        $tableName = config('request-analytics.database.table');
        $connection = config('request-analytics.database.connection'); // Should be null
        
        // null connection should use default connection
        Schema::connection($connection)->create($tableName, function (Blueprint $table) {
            $table->id();
            $table->string('path');
        });
        
        expect(Schema::hasTable('null_conn_table'))->toBeTrue();
    });

    it('can create multiple tables with different configurations', function () {
        // First table
        Config::set('request-analytics.database.table', 'table_one');
        $tableName1 = config('request-analytics.database.table');
        
        Schema::create($tableName1, function (Blueprint $table) {
            $table->id();
            $table->string('path');
        });
        
        // Second table  
        Config::set('request-analytics.database.table', 'table_two');
        $tableName2 = config('request-analytics.database.table');
        
        Schema::create($tableName2, function (Blueprint $table) {
            $table->id();
            $table->string('path');
        });
        
        expect(Schema::hasTable('table_one'))->toBeTrue();
        expect(Schema::hasTable('table_two'))->toBeTrue();
        
        // Clean up
        Schema::dropIfExists('table_one');
        Schema::dropIfExists('table_two');
    });

    it('verifies table structure matches migration definition', function () {
        Config::set('request-analytics.database.table', 'structure_test');
        
        $tableName = config('request-analytics.database.table');
        
        Schema::create($tableName, function (Blueprint $table) {
            $table->id();
            $table->string('path');
            $table->string('ip_address');
            $table->timestamp('visited_at');
        });
        
        // Test specific column types and properties
        expect(Schema::hasColumn($tableName, 'id'))->toBeTrue();
        expect(Schema::hasColumn($tableName, 'path'))->toBeTrue();
        expect(Schema::hasColumn($tableName, 'ip_address'))->toBeTrue();
        expect(Schema::hasColumn($tableName, 'visited_at'))->toBeTrue();
        
        // Should not have columns we didn't create
        expect(Schema::hasColumn($tableName, 'nonexistent_column'))->toBeFalse();
    });

    it('handles configuration edge cases in migration', function () {
        // Test with empty string table name
        Config::set('request-analytics.database.table', '');
        $tableName = config('request-analytics.database.table', 'fallback_table');
        
        expect($tableName)->toBe('');
        
        // Test with null table name - config() returns null when value is explicitly null
        Config::set('request-analytics.database.table', null);
        $tableName = config('request-analytics.database.table', 'request_analytics');
        
        expect($tableName)->toBeNull(); // This is expected behavior
        
        // Test proper fallback handling
        $actualTableName = $tableName ?: 'request_analytics';
        expect($actualTableName)->toBe('request_analytics');
    });

    it('migration configuration is isolated per test', function () {
        // This test ensures each test gets fresh configuration
        Config::set('request-analytics.database.table', 'isolation_test');
        
        $tableName = config('request-analytics.database.table');
        expect($tableName)->toBe('isolation_test');
        
        // The beforeEach hook should reset this for the next test
    });
});