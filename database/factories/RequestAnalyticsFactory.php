<?php

namespace MeShaon\RequestAnalytics\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use MeShaon\RequestAnalytics\Models\RequestAnalytics;

/**
 * @extends Factory<RequestAnalytics>
 */
class RequestAnalyticsFactory extends Factory
{
    protected $model = RequestAnalytics::class;

    public function definition(): array
    {
        $browsers = ['Chrome', 'Firefox', 'Safari', 'Edge', 'Opera'];
        $devices = ['Desktop', 'Mobile', 'Tablet'];
        $operatingSystems = ['Windows 10', 'Mac OS X', 'Linux', 'iOS', 'Android'];
        $countries = ['US', 'CA', 'UK', 'DE', 'FR', 'AU', 'JP'];
        $cities = ['New York', 'Toronto', 'London', 'Berlin', 'Paris', 'Sydney', 'Tokyo'];
        $referrers = [
            'https://google.com',
            'https://facebook.com',
            'https://twitter.com',
            'https://linkedin.com',
            'https://reddit.com',
            null,
        ];

        return [
            'path' => $this->faker->randomElement([
                '/',
                '/about',
                '/contact',
                '/products',
                '/services',
                '/blog',
                '/pricing',
                '/faq',
            ]),
            'page_title' => $this->faker->sentence(3),
            'ip_address' => $this->faker->ipv4(),
            'operating_system' => $this->faker->randomElement($operatingSystems),
            'browser' => $this->faker->randomElement($browsers),
            'device' => $this->faker->randomElement($devices),
            'screen' => $this->faker->randomElement(['1920x1080', '1366x768', '414x896', '768x1024']),
            'referrer' => $this->faker->randomElement($referrers),
            'country' => $this->faker->randomElement($countries),
            'city' => $this->faker->randomElement($cities),
            'language' => $this->faker->randomElement(['en-US', 'en-CA', 'en-GB', 'de-DE', 'fr-FR']),
            'query_params' => $this->faker->randomElement([
                '{}',
                '{"utm_source":"google"}',
                '{"utm_source":"facebook","utm_medium":"social"}',
                '{"ref":"homepage"}',
            ]),
            'session_id' => $this->faker->uuid(),
            'visitor_id' => $this->faker->uuid(),
            'user_id' => $this->faker->optional(0.3)->numberBetween(1, 100),
            'http_method' => $this->faker->randomElement(['GET', 'POST', 'PUT', 'DELETE']),
            'request_category' => $this->faker->randomElement(['web', 'api']),
            'response_time' => $this->faker->numberBetween(50, 2000),
            'visited_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
        ];
    }
}
