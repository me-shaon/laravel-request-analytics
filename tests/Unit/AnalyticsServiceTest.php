<?php

declare(strict_types=1);

namespace MeShaon\RequestAnalytics\Tests\Unit;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use MeShaon\RequestAnalytics\Models\RequestAnalytics;
use MeShaon\RequestAnalytics\Services\AnalyticsService;
use MeShaon\RequestAnalytics\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class AnalyticsServiceTest extends TestCase
{
    use RefreshDatabase;

    private AnalyticsService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AnalyticsService;
    }

    #[Test]
    public function it_gets_date_range_from_date_range_parameter(): void
    {
        $params = ['date_range' => 7];

        $result = $this->service->getDateRange($params);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('start', $result);
        $this->assertArrayHasKey('end', $result);
        $this->assertArrayHasKey('days', $result);
        $this->assertArrayHasKey('key', $result);
        $this->assertEquals(7, $result['days']);
    }

    #[Test]
    public function it_gets_date_range_from_start_and_end_dates(): void
    {
        $params = [
            'start_date' => '2024-01-01',
            'end_date' => '2024-01-31',
        ];

        $result = $this->service->getDateRange($params);

        $this->assertInstanceOf(Carbon::class, $result['start']);
        $this->assertInstanceOf(Carbon::class, $result['end']);
        $this->assertEquals('2024-01-01', $result['start']->format('Y-m-d'));
        $this->assertEquals('2024-01-31', $result['end']->format('Y-m-d'));
        $this->assertEquals(30, $result['days']);
    }

    #[Test]
    public function it_uses_default_date_range_when_no_parameters(): void
    {
        $params = [];

        $result = $this->service->getDateRange($params);

        $this->assertEquals(30, $result['days']);
    }

    #[Test]
    public function it_gets_base_query_with_date_range(): void
    {
        $dateRange = [
            'start' => Carbon::now()->subDays(7),
            'end' => Carbon::now(),
        ];

        $query = $this->service->getBaseQuery($dateRange);

        $this->assertInstanceOf(Builder::class, $query);
    }

    #[Test]
    public function it_gets_summary_data(): void
    {
        RequestAnalytics::factory()->count(5)->create([
            'visitor_id' => 'visitor-1',
            'session_id' => 'session-1',
            'response_time' => 100,
        ]);
        RequestAnalytics::factory()->count(3)->create([
            'visitor_id' => 'visitor-2',
            'session_id' => 'session-2',
            'response_time' => 200,
        ]);

        $query = RequestAnalytics::query();
        $dateRange = $this->service->getDateRange(['date_range' => 30]);
        $result = $this->service->getSummary($query, $dateRange);

        $this->assertIsArray($result);
        $this->assertEquals(8, $result['views']);
        $this->assertEquals(2, $result['visitors']);
        $this->assertArrayHasKey('bounce_rate', $result);
        $this->assertArrayHasKey('average_visit_time', $result);
    }

    #[Test]
    public function it_gets_summary_with_no_data(): void
    {
        $query = RequestAnalytics::query();
        $dateRange = $this->service->getDateRange(['date_range' => 30]);
        $result = $this->service->getSummary($query, $dateRange);

        $this->assertEquals(0, $result['views']);
        $this->assertEquals(0, $result['visitors']);
        $this->assertArrayHasKey('bounce_rate', $result);
        $this->assertArrayHasKey('average_visit_time', $result);
    }

    #[Test]
    public function it_gets_chart_data(): void
    {
        $visitedAt = Carbon::now()->subDays(2);

        RequestAnalytics::factory()->count(3)->create([
            'visitor_id' => 'visitor-1',
            'visited_at' => $visitedAt,
        ]);
        RequestAnalytics::factory()->count(2)->create([
            'visitor_id' => 'visitor-2',
            'visited_at' => $visitedAt->copy()->addDay(),
        ]);

        $dateRange = [
            'start' => $visitedAt->copy()->startOfDay(),
            'end' => $visitedAt->copy()->addDays(2)->endOfDay(),
        ];

        $query = RequestAnalytics::whereBetween('visited_at', [$dateRange['start'], $dateRange['end']]);
        $result = $this->service->getChartData($query, $dateRange);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('labels', $result);
        $this->assertArrayHasKey('datasets', $result);
        $this->assertCount(3, $result['labels']);
        $this->assertCount(2, $result['datasets']);
    }

    #[Test]
    public function it_gets_top_pages(): void
    {
        RequestAnalytics::factory()->count(5)->create(['path' => '/home']);
        RequestAnalytics::factory()->count(3)->create(['path' => '/about']);
        RequestAnalytics::factory()->count(2)->create(['path' => '/contact']);

        $query = RequestAnalytics::query();
        $result = $this->service->getTopPages($query);

        $this->assertIsArray($result);
        $this->assertCount(3, $result);
        $this->assertEquals('/home', $result[0]['path']);
        $this->assertEquals(5, $result[0]['views']);
    }

    #[Test]
    public function it_gets_top_pages_with_percentages(): void
    {
        RequestAnalytics::factory()->count(7)->create(['path' => '/home']);
        RequestAnalytics::factory()->count(3)->create(['path' => '/about']);

        $query = RequestAnalytics::query();
        $result = $this->service->getTopPages($query, true);

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertEquals(70.0, $result[0]['percentage']);
        $this->assertEquals(30.0, $result[1]['percentage']);
    }

    #[Test]
    public function it_gets_top_pages_with_percentages_empty_data(): void
    {
        $query = RequestAnalytics::query();
        $result = $this->service->getTopPages($query, true);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    #[Test]
    public function it_gets_top_referrers(): void
    {
        RequestAnalytics::factory()->count(5)->create(['referrer' => 'https://google.com']);
        RequestAnalytics::factory()->count(3)->create(['referrer' => 'https://facebook.com']);
        RequestAnalytics::factory()->create(['referrer' => '']);
        RequestAnalytics::factory()->create(['referrer' => null]);

        $query = RequestAnalytics::query();
        $result = $this->service->getTopReferrers($query);

        $this->assertIsArray($result);
        // Should filter out null and empty referrers, leaving us with valid ones
        $this->assertGreaterThanOrEqual(1, count($result));
        $this->assertLessThanOrEqual(2, count($result));
    }

    #[Test]
    public function it_gets_browsers_data(): void
    {
        RequestAnalytics::factory()->count(5)->create(['browser' => 'Chrome']);
        RequestAnalytics::factory()->count(3)->create(['browser' => 'Firefox']);
        RequestAnalytics::factory()->create(['browser' => null]);

        $query = RequestAnalytics::query();
        $result = $this->service->getBrowsersData($query, false);

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertEquals('Chrome', $result[0]['browser']);
        $this->assertEquals(5, $result[0]['count']);
    }

    #[Test]
    public function it_gets_browsers_data_with_percentages(): void
    {
        RequestAnalytics::factory()->count(7)->create(['browser' => 'Chrome']);
        RequestAnalytics::factory()->count(3)->create(['browser' => 'Firefox']);

        $query = RequestAnalytics::query();
        $result = $this->service->getBrowsersData($query, true);

        $this->assertIsArray($result);
        $this->assertEquals(70.0, $result[0]['percentage']);
        $this->assertEquals(30.0, $result[1]['percentage']);
    }

    #[Test]
    public function it_gets_devices_data(): void
    {
        RequestAnalytics::factory()->count(6)->create(['device' => 'Desktop']);
        RequestAnalytics::factory()->count(4)->create(['device' => 'Mobile']);

        $query = RequestAnalytics::query();
        $result = $this->service->getDevices($query);

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertEquals('Desktop', $result[0]['device']);
        $this->assertEquals(6, $result[0]['count']);
    }

    #[Test]
    public function it_gets_devices_data_with_percentages(): void
    {
        RequestAnalytics::factory()->count(8)->create(['device' => 'Desktop']);
        RequestAnalytics::factory()->count(2)->create(['device' => 'Mobile']);

        $query = RequestAnalytics::query();
        $result = $this->service->getDevices($query, true);

        $this->assertIsArray($result);
        $this->assertEquals(80.0, $result[0]['percentage']);
        $this->assertEquals(20.0, $result[1]['percentage']);
    }

    #[Test]
    public function it_gets_countries_data(): void
    {
        RequestAnalytics::factory()->count(5)->create(['country' => 'US']);
        RequestAnalytics::factory()->count(3)->create(['country' => 'CA']);
        RequestAnalytics::factory()->create(['country' => null]);

        $query = RequestAnalytics::query();
        $result = $this->service->getCountriesData($query, false);

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertEquals('US', $result[0]['country']);
        $this->assertEquals(5, $result[0]['count']);
    }

    #[Test]
    public function it_gets_operating_systems_data(): void
    {
        RequestAnalytics::factory()->count(4)->create([
            'operating_system' => 'Windows 10',
            'session_id' => 'session-1',
        ]);
        RequestAnalytics::factory()->count(2)->create([
            'operating_system' => 'Mac OS X',
            'session_id' => 'session-2',
        ]);

        $query = RequestAnalytics::query();
        $result = $this->service->getOperatingSystems($query);

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertEquals('Windows 10', $result[0]['name']);
        $this->assertEquals(1, $result[0]['count']);
    }

    #[Test]
    public function it_gets_operating_systems_data_with_percentages(): void
    {
        RequestAnalytics::factory()->count(3)->create([
            'operating_system' => 'Windows 10',
            'session_id' => 'session-1',
        ]);
        RequestAnalytics::factory()->count(1)->create([
            'operating_system' => 'Mac OS X',
            'session_id' => 'session-2',
        ]);

        $query = RequestAnalytics::query();
        $result = $this->service->getOperatingSystems($query, true);

        $this->assertIsArray($result);
        $this->assertEquals(50.0, $result[0]['percentage']);
        $this->assertEquals(50.0, $result[1]['percentage']);
    }

    #[Test]
    public function it_gets_overview_data(): void
    {
        RequestAnalytics::factory()->count(10)->create();

        $params = ['date_range' => 30];
        $result = $this->service->getOverviewData($params);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('summary', $result);
        $this->assertArrayHasKey('chart', $result);
        $this->assertArrayHasKey('top_pages', $result);
        $this->assertArrayHasKey('top_referrers', $result);
        $this->assertArrayHasKey('browsers', $result);
        $this->assertArrayHasKey('devices', $result);
        $this->assertArrayHasKey('countries', $result);
        $this->assertArrayHasKey('operating_systems', $result);
    }

    #[Test]
    public function it_gets_visitors_paginated(): void
    {
        RequestAnalytics::factory()->count(25)->create();

        $params = ['date_range' => 30];
        $result = $this->service->getVisitors($params, 10);

        $this->assertEquals(10, $result->perPage());
        $this->assertLessThanOrEqual(25, $result->total());
    }

    #[Test]
    public function it_gets_page_views_paginated(): void
    {
        RequestAnalytics::factory()->count(30)->create();

        $params = ['date_range' => 30];
        $result = $this->service->getPageViews($params, 15);

        $this->assertEquals(15, $result->perPage());
        $this->assertEquals(30, $result->total());
    }

    #[Test]
    public function it_gets_page_views_with_path_filter(): void
    {
        RequestAnalytics::factory()->count(5)->create(['path' => '/home']);
        RequestAnalytics::factory()->count(3)->create(['path' => '/about']);

        $params = ['date_range' => 30, 'path' => 'home'];
        $result = $this->service->getPageViews($params, 10);

        $this->assertEquals(5, $result->total());
    }

    #[Test]
    public function it_gets_correct_domain_expression_for_sqlite(): void
    {
        $result = $this->service->getDomainExpression('referrer');

        $this->assertStringContainsString('CASE', $result);
        $this->assertStringContainsString('referrer', $result);
    }

    #[Test]
    public function it_gets_correct_duration_expression_for_sqlite(): void
    {
        $result = $this->service->getDurationExpression('visited_at');

        $this->assertStringContainsString('julianday', $result);
        $this->assertStringContainsString('visited_at', $result);
    }
}
