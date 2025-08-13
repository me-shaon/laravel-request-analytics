<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use MeShaon\RequestAnalytics\Models\RequestAnalytics;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Create test data
    RequestAnalytics::factory()->count(50)->create();
});

it('returns analytics overview', function () {
    $response = $this->getJson('/api/v1/analytics/overview');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'data' => [
                'summary' => [
                    'total_views',
                    'unique_visitors',
                    'unique_sessions',
                    'avg_response_time',
                ],
                'chart',
                'top_pages',
                'top_referrers',
                'browsers',
                'devices',
                'countries',
            ],
            'date_range',
        ]);
});

it('returns paginated visitors', function () {
    $response = $this->getJson('/api/v1/analytics/visitors?per_page=10');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'data' => [
                'current_page',
                'data',
                'per_page',
                'total',
            ],
        ]);
});

it('filters page views by path', function () {
    RequestAnalytics::factory()->create(['path' => '/test-page']);

    $response = $this->getJson('/api/v1/analytics/page-views?path=test-page');

    $response->assertStatus(200);

    $data = $response->json('data.data');
    foreach ($data as $item) {
        expect($item['path'])->toContain('test-page');
    }
});

it('validates date range parameters', function () {
    $response = $this->getJson('/api/v1/analytics/overview?date_range=400');

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['date_range']);
});

it('queues export job', function () {
    $response = $this->postJson('/api/v1/analytics/export', [
        'format' => 'csv',
        'type' => 'overview',
        'date_range' => 30,
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'message',
            'job_id',
        ]);
});
