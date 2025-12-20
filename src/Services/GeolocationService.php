<?php

namespace MeShaon\RequestAnalytics\Services;

use GeoIp2\Database\Reader;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use MeShaon\RequestAnalytics\Exceptions\AddressNotFoundException;
use MeShaon\RequestAnalytics\Exceptions\GeolocationProviderException;
use MeShaon\RequestAnalytics\Exceptions\MaxMindConfigurationException;
use MeShaon\RequestAnalytics\Exceptions\MaxMindDependencyException;

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

            throw new GeolocationProviderException('ipapi', $ip, $e->getMessage(), $e->getCode(), $e);
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

            throw new GeolocationProviderException('ipgeolocation', $ip, $e->getMessage(), $e->getCode(), $e);
        }

        return $this->getDefaultLocation();
    }

    protected function lookupWithMaxMind(string $ip): array
    {
        $maxmindType = config('request-analytics.geolocation.maxmind.type', 'webservice');

        return match ($maxmindType) {
            'database' => $this->lookupWithMaxMindDatabase($ip),
            'webservice' => $this->lookupWithMaxMindWebService($ip),
            default => $this->getDefaultLocation(),
        };
    }

    protected function lookupWithMaxMindWebService(string $ip): array
    {
        $userId = config('request-analytics.geolocation.maxmind.user_id');
        $licenseKey = config('request-analytics.geolocation.maxmind.license_key');

        if (! $userId || ! $licenseKey) {
            Log::warning('MaxMind web service credentials not configured');

            throw new MaxMindConfigurationException(
                'webservice',
                'MaxMind web service requires both user_id and license_key to be configured'
            );
        }

        try {
            $response = Http::timeout(10)
                ->withBasicAuth($userId, $licenseKey)
                ->get("https://geoip.maxmind.com/geoip/v2.1/city/{$ip}");

            if ($response->successful()) {
                $data = $response->json();

                return [
                    'country' => $data['country']['names']['en'] ?? '',
                    'country_code' => $data['country']['iso_code'] ?? '',
                    'region' => $data['subdivisions'][0]['names']['en'] ?? '',
                    'city' => $data['city']['names']['en'] ?? '',
                    'latitude' => $data['location']['latitude'] ?? null,
                    'longitude' => $data['location']['longitude'] ?? null,
                    'timezone' => $data['location']['time_zone'] ?? '',
                    'isp' => $data['traits']['isp'] ?? '',
                ];
            }

            if ($response->status() === 404) {
                // IP not found in database
                return $this->getDefaultLocation();
            }

            Log::warning('MaxMind web service returned error', [
                'ip' => $ip,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

        } catch (\Exception $e) {
            Log::warning('MaxMind web service lookup failed', [
                'ip' => $ip,
                'error' => $e->getMessage(),
            ]);

            throw new GeolocationProviderException('maxmind-webservice', $ip, $e->getMessage(), $e->getCode(), $e);
        }

        return $this->getDefaultLocation();
    }

    protected function lookupWithMaxMindDatabase(string $ip): array
    {
        $databasePath = config('request-analytics.geolocation.maxmind.database_path');

        if (! $databasePath || ! file_exists($databasePath)) {
            Log::warning('MaxMind database file not found', [
                'path' => $databasePath,
            ]);

            throw new MaxMindConfigurationException(
                'database',
                "MaxMind database file not found at path: {$databasePath}"
            );
        }

        // Check if GeoIP2 library is available
        if (! class_exists('GeoIp2\Database\Reader')) {
            Log::warning('GeoIP2 library not installed. Please run: composer require geoip2/geoip2');

            throw new MaxMindDependencyException(
                'geoip2/geoip2',
                'GeoIP2 library not installed. Please run: composer require geoip2/geoip2'
            );
        }

        try {
            $reader = new Reader($databasePath);
            $record = $reader->city($ip);

            return [
                'country' => $record->country->name ?? '',
                'country_code' => $record->country->isoCode ?? '',
                'region' => $record->mostSpecificSubdivision->name ?? '',
                'city' => $record->city->name ?? '',
                'latitude' => $record->location->latitude,
                'longitude' => $record->location->longitude,
                'timezone' => $record->location->timeZone ?? '',
                'isp' => '', // ISP data requires separate database
            ];

        } catch (AddressNotFoundException) {
            // IP not found in database - this is normal for some IPs
            return $this->getDefaultLocation();
        } catch (\Exception $e) {
            Log::warning('MaxMind database lookup failed', [
                'ip' => $ip,
                'database' => $databasePath,
                'error' => $e->getMessage(),
            ]);

            throw new GeolocationProviderException('maxmind-database', $ip, $e->getMessage(), $e->getCode(), $e);
        }
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
