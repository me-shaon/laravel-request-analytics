<?php

namespace MeShaon\RequestAnalytics\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeolocationService
{
    protected string $provider;

    protected ?string $apiKey;

    protected int $cacheMinutes = 1440; // 24 hours

    public function __construct()
    {
        $this->provider = config('request-analytics.geolocation.provider', 'ipapi');
        $this->apiKey = config('request-analytics.geolocation.api_key');
    }

    public function lookup(string $ip): array
    {
        // Check if it's a local IP
        if ($this->isLocalIp($ip)) {
            return $this->getDefaultLocation();
        }

        // Check cache first
        $cacheKey = "geo_location_{$ip}";
        if ($cached = Cache::get($cacheKey)) {
            return $cached;
        }

        // Perform lookup based on provider
        $location = match ($this->provider) {
            'ipapi' => $this->lookupWithIpApi($ip),
            'ipgeolocation' => $this->lookupWithIpGeolocation($ip),
            'maxmind' => $this->lookupWithMaxMind($ip),
            default => $this->getDefaultLocation(),
        };

        // Cache the result
        Cache::put($cacheKey, $location, now()->addMinutes($this->cacheMinutes));

        return $location;
    }

    protected function lookupWithIpApi(string $ip): array
    {
        try {
            $response = Http::timeout(5)->get("http://ip-api.com/json/{$ip}", [
                'fields' => 'status,country,countryCode,region,regionName,city,lat,lon,timezone,isp',
            ]);

            if ($response->successful()) {
                $data = $response->json();
                if ($data['status'] === 'success') {
                    return [
                        'country' => $data['country'] ?? '',
                        'country_code' => $data['countryCode'] ?? '',
                        'region' => $data['regionName'] ?? '',
                        'city' => $data['city'] ?? '',
                        'latitude' => $data['lat'] ?? null,
                        'longitude' => $data['lon'] ?? null,
                        'timezone' => $data['timezone'] ?? '',
                        'isp' => $data['isp'] ?? '',
                    ];
                }
            }
        } catch (\Exception $e) {
            Log::warning('IP geolocation lookup failed', [
                'ip' => $ip,
                'error' => $e->getMessage(),
            ]);
        }

        return $this->getDefaultLocation();
    }

    protected function lookupWithIpGeolocation(string $ip): array
    {
        if (! $this->apiKey) {
            return $this->getDefaultLocation();
        }

        try {
            $response = Http::timeout(5)->get('https://api.ipgeolocation.io/ipgeo', [
                'apiKey' => $this->apiKey,
                'ip' => $ip,
                'fields' => 'country_name,country_code2,state_prov,city,latitude,longitude,time_zone,isp',
            ]);

            if ($response->successful()) {
                $data = $response->json();

                return [
                    'country' => $data['country_name'] ?? '',
                    'country_code' => $data['country_code2'] ?? '',
                    'region' => $data['state_prov'] ?? '',
                    'city' => $data['city'] ?? '',
                    'latitude' => $data['latitude'] ?? null,
                    'longitude' => $data['longitude'] ?? null,
                    'timezone' => $data['time_zone']['name'] ?? '',
                    'isp' => $data['isp'] ?? '',
                ];
            }
        } catch (\Exception $e) {
            Log::warning('IP geolocation lookup failed', [
                'ip' => $ip,
                'provider' => 'ipgeolocation',
                'error' => $e->getMessage(),
            ]);
        }

        return $this->getDefaultLocation();
    }

    protected function lookupWithMaxMind(string $ip): array
    {
        // This would require the GeoIP2 PHP library
        // composer require geoip2/geoip2
        // Implementation would depend on whether using web service or local database

        return $this->getDefaultLocation();
    }

    protected function isLocalIp(string $ip): bool
    {
        return in_array($ip, ['127.0.0.1', '::1']) ||
               str_starts_with($ip, '192.168.') ||
               str_starts_with($ip, '10.') ||
               str_starts_with($ip, '172.');
    }

    protected function getDefaultLocation(): array
    {
        return [
            'country' => '',
            'country_code' => '',
            'region' => '',
            'city' => '',
            'latitude' => null,
            'longitude' => null,
            'timezone' => '',
            'isp' => '',
        ];
    }
}
