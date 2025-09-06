<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Session;
use MeShaon\RequestAnalytics\Http\DTO\RequestDataDTO;
use MeShaon\RequestAnalytics\Models\RequestAnalytics;
use MeShaon\RequestAnalytics\Services\RequestAnalyticsService;

describe('Service Integration with Configuration', function () {
    beforeEach(function () {
        // Set up test database table configuration
        Config::set('request-analytics.database.table', 'test_analytics');
        Config::set('request-analytics.database.connection', null);

        // Start a session for testing
        Session::start();

        $tableName = config('request-analytics.database.table');

        // Clean up existing table
        if (Schema::hasTable($tableName)) {
            Schema::dropIfExists($tableName);
        }

        // Create test table
        if (! Schema::hasTable($tableName)) {
            Schema::create($tableName, function (Blueprint $table) {
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
        }
    });

    afterEach(function () {
        // Clean up after each test
        $tableName = config('request-analytics.database.table', 'test_analytics');
        if (Schema::hasTable($tableName)) {
            Schema::dropIfExists($tableName);
        }
    });

    it('stores data in configured table', function () {
        $service = new RequestAnalyticsService;

        $dto = new RequestDataDTO(
            path: '/test',
            content: '<title>Test Page</title>',
            browserInfo: [
                'operating_system' => 'Linux',
                'browser' => 'Chrome',
                'device' => 'Desktop',
            ],
            ipAddress: '127.0.0.1',
            referrer: '',
            country: 'US',
            language: 'en',
            queryParams: '{}',
            httpMethod: 'GET',
            responseTime: 100,
            requestCategory: 'web'
        );

        $result = $service->store($dto);

        expect($result)->toBeInstanceOf(RequestAnalytics::class);
        expect($result->path)->toBe('/test');
        expect($result->getTable())->toBe('test_analytics');
        expect($result->page_title)->toBe('Test Page');
        expect($result->ip_address)->toBe('127.0.0.1');
        expect($result->operating_system)->toBe('Linux');
        expect($result->browser)->toBe('Chrome');
        expect($result->device)->toBe('Desktop');
    });

    it('service works with custom table name', function () {
        Config::set('request-analytics.database.table', 'custom_service_table');

        // Create the custom table
        Schema::create('custom_service_table', function (Blueprint $table) {
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

        $service = new RequestAnalyticsService;

        $dto = new RequestDataDTO(
            path: '/custom',
            content: '<title>Custom Page</title>',
            browserInfo: [
                'operating_system' => 'Windows',
                'browser' => 'Firefox',
                'device' => 'Mobile',
            ],
            ipAddress: '192.168.1.1',
            referrer: 'https://google.com',
            country: 'CA',
            language: 'fr',
            queryParams: '{"q":"test"}',
            httpMethod: 'POST',
            responseTime: 250,
            requestCategory: 'api'
        );

        $result = $service->store($dto);

        expect($result->getTable())->toBe('custom_service_table');
        expect($result->path)->toBe('/custom');
        expect($result->page_title)->toBe('Custom Page');
        expect($result->country)->toBe('CA');
        expect($result->http_method)->toBe('POST');
        expect($result->request_category)->toBe('api');

        // Clean up
        Schema::dropIfExists('custom_service_table');
    });

    it('extracts page title correctly', function () {
        $service = new RequestAnalyticsService;

        $testCases = [
            '<title>Simple Title</title>' => 'Simple Title',
            '<html><head><title>Complex Page</title></head></html>' => 'Complex Page',
            '<TITLE>Uppercase Tag</TITLE>' => 'Uppercase Tag',
            '<title>Title with <span>HTML</span></title>' => 'Title with <span>HTML</span>',
            'No title tag' => '',
            '<title></title>' => '',
        ];

        foreach ($testCases as $html => $expectedTitle) {
            $dto = new RequestDataDTO(
                path: '/title-test',
                content: $html,
                browserInfo: ['operating_system' => 'Linux', 'browser' => 'Chrome', 'device' => 'Desktop'],
                ipAddress: '127.0.0.1',
                referrer: '',
                country: 'US',
                language: 'en',
                queryParams: '{}',
                httpMethod: 'GET',
                responseTime: 100,
                requestCategory: 'web'
            );

            $result = $service->store($dto);
            expect($result->page_title)->toBe($expectedTitle);

            // Clean up for next iteration
            $result->delete();
        }
    });

    it('handles session data correctly', function () {
        $service = new RequestAnalyticsService;

        // Ensure session is available
        expect(session()->getId())->not->toBeEmpty();

        $dto = new RequestDataDTO(
            path: '/session-test',
            content: '<title>Session Test</title>',
            browserInfo: ['operating_system' => 'Linux', 'browser' => 'Chrome', 'device' => 'Desktop'],
            ipAddress: '127.0.0.1',
            referrer: '',
            country: 'US',
            language: 'en',
            queryParams: '{}',
            httpMethod: 'GET',
            responseTime: 100,
            requestCategory: 'web'
        );

        $result = $service->store($dto);

        expect($result->session_id)->toBe(session()->getId());
        expect($result->session_id)->not->toBeEmpty();
    });

    it('stores all DTO data correctly', function () {
        $service = new RequestAnalyticsService;

        $dto = new RequestDataDTO(
            path: '/complete-test',
            content: '<title>Complete Test Page</title>',
            browserInfo: [
                'operating_system' => 'macOS',
                'browser' => 'Safari',
                'device' => 'Tablet',
            ],
            ipAddress: '10.0.0.1',
            referrer: 'https://example.com/referrer',
            country: 'UK',
            language: 'en-GB',
            queryParams: '{"param1":"value1","param2":"value2"}',
            httpMethod: 'PUT',
            responseTime: 500,
            requestCategory: 'api'
        );

        $result = $service->store($dto);

        // Verify all fields are stored correctly
        expect($result->path)->toBe('/complete-test');
        expect($result->page_title)->toBe('Complete Test Page');
        expect($result->ip_address)->toBe('10.0.0.1');
        expect($result->operating_system)->toBe('macOS');
        expect($result->browser)->toBe('Safari');
        expect($result->device)->toBe('Tablet');
        expect($result->referrer)->toBe('https://example.com/referrer');
        expect($result->country)->toBe('UK');
        expect($result->language)->toBe('en-GB');
        expect($result->query_params)->toBe('{"param1":"value1","param2":"value2"}');
        expect($result->http_method)->toBe('PUT');
        expect($result->request_category)->toBe('api');
        expect($result->response_time)->toBe(500);
        expect($result->user_id)->toBeNull(); // No authenticated user in tests
        expect($result->visited_at)->not->toBeNull();
    });

    it('handles configuration changes during runtime', function () {
        // Start with one table
        Config::set('request-analytics.database.table', 'runtime_table_1');

        Schema::create('runtime_table_1', function (Blueprint $table) {
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

        $service = new RequestAnalyticsService;

        $dto1 = new RequestDataDTO(
            path: '/runtime-1',
            content: '<title>Runtime 1</title>',
            browserInfo: ['operating_system' => 'Linux', 'browser' => 'Chrome', 'device' => 'Desktop'],
            ipAddress: '127.0.0.1',
            referrer: '',
            country: 'US',
            language: 'en',
            queryParams: '{}',
            httpMethod: 'GET',
            responseTime: 100,
            requestCategory: 'web'
        );

        $result1 = $service->store($dto1);
        expect($result1->getTable())->toBe('runtime_table_1');

        // Change configuration and create new table
        Config::set('request-analytics.database.table', 'runtime_table_2');

        Schema::create('runtime_table_2', function (Blueprint $table) {
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

        $dto2 = new RequestDataDTO(
            path: '/runtime-2',
            content: '<title>Runtime 2</title>',
            browserInfo: ['operating_system' => 'Windows', 'browser' => 'Edge', 'device' => 'Mobile'],
            ipAddress: '127.0.0.2',
            referrer: '',
            country: 'CA',
            language: 'fr',
            queryParams: '{}',
            httpMethod: 'GET',
            responseTime: 150,
            requestCategory: 'web'
        );

        $result2 = $service->store($dto2);
        expect($result2->getTable())->toBe('runtime_table_2');

        // Verify both records exist in their respective tables
        expect($result1->path)->toBe('/runtime-1');
        expect($result2->path)->toBe('/runtime-2');

        // Clean up
        Schema::dropIfExists('runtime_table_1');
        Schema::dropIfExists('runtime_table_2');
    });
});
