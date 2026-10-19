<?php

declare(strict_types=1);

namespace MeShaon\RequestAnalytics\Tests\Feature\Web;

use MeShaon\RequestAnalytics\Models\RequestAnalytics;
use MeShaon\RequestAnalytics\Tests\Feature\BaseFeatureTestCase;
use PHPUnit\Framework\Attributes\Test;

class DashboardTest extends BaseFeatureTestCase
{
    #[Test]
    public function it_returns_dashboard_view(): void
    {
        RequestAnalytics::factory()->count(10)->create();

        $response = $this->get(route('request-analytics.dashboard'));

        $response->assertOk()
            ->assertViewIs('request-analytics::analytics');
    }

    #[Test]
    public function it_hides_the_back_link_by_default(): void
    {
        $this->get(route('request-analytics.dashboard'))
            ->assertOk()
            ->assertDontSee('Back to site');
    }

    #[Test]
    public function it_shows_the_configured_back_link(): void
    {
        config([
            'request-analytics.back_link.url' => '/admin',
            'request-analytics.back_link.label' => 'Back to dashboard',
        ]);

        $this->get(route('request-analytics.dashboard'))
            ->assertOk()
            ->assertSee('href="/admin"', false)
            ->assertSee('Back to dashboard');
    }

    #[Test]
    public function it_returns_dashboard_view_with_empty_data(): void
    {
        $response = $this->get(route('request-analytics.dashboard'));

        $response->assertOk()
            ->assertViewIs('request-analytics::analytics');
    }

    #[Test]
    public function it_accepts_date_range_parameter(): void
    {
        RequestAnalytics::factory()->count(15)->create();

        $response = $this->get(route('request-analytics.dashboard', ['date_range' => 7]));

        $response->assertOk()
            ->assertViewIs('request-analytics::analytics');
    }

    #[Test]
    public function it_handles_invalid_date_range_parameter(): void
    {
        RequestAnalytics::factory()->count(5)->create();

        $response = $this->get(route('request-analytics.dashboard', ['date_range' => 'invalid']));

        $response->assertOk()
            ->assertViewIs('request-analytics::analytics');
    }

    #[Test]
    public function it_handles_negative_date_range_parameter(): void
    {
        RequestAnalytics::factory()->count(5)->create();

        $response = $this->get(route('request-analytics.dashboard', ['date_range' => -10]));

        $response->assertOk()
            ->assertViewIs('request-analytics::analytics');
    }

    #[Test]
    public function it_handles_zero_date_range_parameter(): void
    {
        RequestAnalytics::factory()->count(5)->create();

        $response = $this->get(route('request-analytics.dashboard', ['date_range' => 0]));

        $response->assertOk()
            ->assertViewIs('request-analytics::analytics');
    }

    #[Test]
    public function it_handles_string_numeric_date_range(): void
    {
        RequestAnalytics::factory()->count(5)->create();

        $response = $this->get(route('request-analytics.dashboard', ['date_range' => '60']));

        $response->assertOk()
            ->assertViewIs('request-analytics::analytics');
    }

    #[Test]
    public function it_displays_analytics_data_with_different_date_ranges(): void
    {
        RequestAnalytics::factory()->count(20)->create();

        foreach ([1, 7, 30, 90] as $dateRange) {
            $response = $this->get(route('request-analytics.dashboard', ['date_range' => $dateRange]));

            $response->assertOk();
        }
    }

    #[Test]
    public function it_filters_by_request_category(): void
    {
        RequestAnalytics::factory()->count(10)->create(['request_category' => 'web']);
        RequestAnalytics::factory()->count(5)->create(['request_category' => 'api']);

        $response = $this->get(route('request-analytics.dashboard', ['request_category' => 'web']));
        $response->assertOk()->assertViewIs('request-analytics::analytics');

        $response = $this->get(route('request-analytics.dashboard', ['request_category' => 'api']));
        $response->assertOk()->assertViewIs('request-analytics::analytics');
    }

    #[Test]
    public function it_handles_combined_date_range_and_request_category_filters(): void
    {
        RequestAnalytics::factory()->count(8)->create(['request_category' => 'web']);
        RequestAnalytics::factory()->count(3)->create(['request_category' => 'api']);

        $response = $this->get(route('request-analytics.dashboard', [
            'date_range' => 7,
            'request_category' => 'web',
        ]));

        $response->assertOk()->assertViewIs('request-analytics::analytics');
    }
}
