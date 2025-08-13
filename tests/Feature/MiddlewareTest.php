<?php

use Illuminate\Support\Facades\Config;
use MeShaon\RequestAnalytics\Models\RequestAnalytics;

beforeEach(function () {
    Config::set('request-analytics.capture.web', true);
    Config::set('request-analytics.capture.api', true);
    Config::set('request-analytics.capture.bots', false);
    Config::set('request-analytics.privacy.respect_dnt', true);
});

it('captures web requests', function () {
    $response = $this->get('/');

    $response->assertStatus(200);

    // Check if request was captured
    $this->assertDatabaseHas('request_analytics', [
        'path' => '/',
        'http_method' => 'GET',
        'request_category' => 'web',
    ]);
});

it('respects Do Not Track header', function () {
    $initialCount = RequestAnalytics::count();

    $response = $this->withHeaders([
        'DNT' => '1',
    ])->get('/');

    $response->assertStatus(200);

    // Should not capture when DNT is set
    expect(RequestAnalytics::count())->toBe($initialCount);
});

it('ignores bot traffic by default', function () {
    $initialCount = RequestAnalytics::count();

    $response = $this->withHeaders([
        'User-Agent' => 'Googlebot/2.1',
    ])->get('/');

    $response->assertStatus(200);

    // Should not capture bot traffic
    expect(RequestAnalytics::count())->toBe($initialCount);
});

it('captures bot traffic when enabled', function () {
    Config::set('request-analytics.capture.bots', true);

    $response = $this->withHeaders([
        'User-Agent' => 'Googlebot/2.1',
    ])->get('/');

    $response->assertStatus(200);

    // Should capture bot traffic when enabled
    $this->assertDatabaseHas('request_analytics', [
        'path' => '/',
    ]);
});

it('ignores configured paths', function () {
    Config::set('request-analytics.ignore-paths', ['admin', 'test']);

    $initialCount = RequestAnalytics::count();

    $response = $this->get('/admin');

    $response->assertStatus(200);

    // Should not capture ignored paths
    expect(RequestAnalytics::count())->toBe($initialCount);
});
