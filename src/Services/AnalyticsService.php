<?php

declare(strict_types=1);

namespace MeShaon\RequestAnalytics\Services;

use Carbon\CarbonInterval;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use MeShaon\RequestAnalytics\Models\RequestAnalytics;

class AnalyticsService
{
    /**
     * @param  array{date_range?: int, start_date?: int, end_date?:int, request_category?:string, with_percentages?:bool}  $params
     * @return array{start: Carbon, end: Carbon, days: int, key: string}
     */
    public function getDateRange(array $params): array
    {
        if (isset($params['start_date']) && isset($params['end_date'])) {
            $startDate = Carbon::parse($params['start_date'])->startOfDay();
            $endDate = Carbon::parse($params['end_date'])->endOfDay();
            $days = (int) $startDate->diffInDays($endDate);
        } else {
            $days = $params['date_range'] ?? 30;
            $startDate = Carbon::now()->subDays($days)->startOfDay();
            $endDate = Carbon::now()->endOfDay();
        }

        return [
            'start' => $startDate,
            'end' => $endDate,
            'days' => $days,
            'key' => $startDate->format('Y-m-d').'_'.$endDate->format('Y-m-d'),
        ];
    }

    /**
     * @param  array<string, mixed>  $dateRange
     * @return Builder<RequestAnalytics>
     */
    public function getBaseQuery(array $dateRange, ?string $requestCategory = null): Builder
    {
        return RequestAnalytics::whereBetween('visited_at', [$dateRange['start'], $dateRange['end']])
            ->when($requestCategory, fn (Builder $query, string $category) => $query->where('request_category', $category));
    }

    /**
     * @param  Builder<RequestAnalytics>  $query
     * @param  array<string, mixed>  $dateRange
     * @return array{views: int, visitors: int, bounce_rate: string, average_visit_time: string}
     */
    public function getSummary(Builder $query, array $dateRange): array
    {
        /* @var int $totalViews */
        $totalViews = (clone $query)->count();
        $uniqueVisitors = $this->getUniqueVisitorCount($query);

        // Calculate bounce rate (percentage of sessions with only one page view)
        $tableName = config('request-analytics.database.table', 'request_analytics');
        $connection = config('request-analytics.database.connection');

        $startDate = clone $dateRange['start'];
        $sessionsWithSinglePageView = DB::connection($connection)->table(function ($query) use ($startDate, $tableName): void {
            $query->from($tableName)
                ->select('session_id')
                ->where('visited_at', '>=', $startDate)
                ->groupBy('session_id')
                ->havingRaw('COUNT(*) = 1');
        }, 'single_page_sessions')->count();

        $bounceRate = $uniqueVisitors > 0
            ? round(($sessionsWithSinglePageView / $uniqueVisitors) * 100, 1)
            : 0;

        // Calculate average visit time
        $durationExpression = $this->getDurationExpression('visited_at');
        $sessionTimes = (clone $query)
            ->select(
                'session_id',
                DB::raw("{$durationExpression} as duration")
            )
            ->groupBy('session_id')
            ->havingRaw("{$durationExpression} > 0")
            ->pluck('duration')
            ->toArray();

        $avgVisitTime = count($sessionTimes) > 0
            ? round(array_sum($sessionTimes) / count($sessionTimes), 1)
            : 0;

        return [
            'views' => $totalViews,
            'visitors' => $uniqueVisitors,
            'bounce_rate' => $bounceRate.'%',
            'average_visit_time' => $this->formatTimeWithCarbon($avgVisitTime),
        ];
    }

    /**
     * @throws \Exception
     */
    protected function formatTimeWithCarbon(float $seconds): string
    {
        if ($seconds <= 0) {
            return '0s';
        }

        return CarbonInterval::seconds($seconds)
            ->cascade()
            ->forHumans([
                'short' => true,
                'minimumUnit' => 'second',
            ]);
    }

    /**
     * @param  Builder<RequestAnalytics>  $query
     * @param  array<string, mixed>  $dateRange
     * @return array{labels: array<int, string>, datasets: array<int, array<string, mixed>>}
     */
    public function getChartData(Builder $query, array $dateRange): array
    {
        $data = (clone $query)
            ->select(
                DB::raw('DATE(visited_at) as date'),
                DB::raw('COUNT(*) as views'),
                DB::raw($this->getUniqueVisitorCountExpression())
            )
            ->groupBy(DB::raw('DATE(visited_at)'))
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        $labels = [];
        $views = [];
        $visitors = [];

        $current = clone $dateRange['start'];
        while ($current <= $dateRange['end']) {
            $dateStr = $current->format('Y-m-d');
            $labels[] = $current->format('M d');

            if ($data->has($dateStr)) {
                /** @var \stdClass $item */
                $item = $data->get($dateStr);
                $views[] = $item->views;
                $visitors[] = $item->unique_visitor_count;
            } else {
                $views[] = 0;
                $visitors[] = 0;
            }

            $current->addDay();
        }

        return [
            'labels' => $labels,
            'datasets' => [
                ['label' => 'Views', 'data' => $views],
                ['label' => 'Visitors', 'data' => $visitors],
            ],
        ];
    }

    /**
     * @param  Builder<RequestAnalytics>  $query
     * @return array<int, array{path: string, views: int, percentage?: float}>
     */
    public function getTopPages(Builder $query, bool $withPercentages = false): array
    {
        /* @var array<int, array{path: string, views: int}> $pages */
        $pages = (clone $query)
            ->select('path', DB::raw('COUNT(*) as views'))
            ->groupBy('path')
            ->orderBy('views', 'desc')
            ->limit(10)
            ->get()
            ->toArray();

        if (! $withPercentages) {
            return $pages;
        }

        $totalViews = array_sum(array_column($pages, 'views'));
        if ($totalViews === 0) {
            return [];
        }

        /** @var array<int, array{path: string, views: int, percentage: float}> */
        return collect($pages)->map(function (array $page) use ($totalViews): array {
            $percentage = round(($page['views'] / $totalViews) * 100, 1);

            return [
                'path' => $page['path'],
                'views' => $page['views'],
                'percentage' => $percentage,
            ];
        })->toArray();
    }

    /**
     * @param  Builder<RequestAnalytics>  $query
     * @return array<int, array{domain: string, visits: int, percentage?: float}>
     */
    public function getTopReferrers($query, bool $withPercentages = false): array
    {

        $domainExpression = $this->getDomainExpression('referrer');

        /* @var array<int, array{domain: string, visits: int}> $referrers */
        $referrers = (clone $query)
            ->select(
                DB::raw("{$domainExpression} as domain"),
                DB::raw('COUNT(*) as visits')
            )
            ->whereNotNull('referrer')
            ->where('referrer', '!=', '')
            ->groupBy(DB::raw($domainExpression))
            ->orderBy('visits', 'desc')
            ->limit(10)
            ->get()
            ->toArray();

        if (! $withPercentages) {
            return $referrers;
        }

        $totalVisits = array_sum(array_column($referrers, 'visits'));
        if ($totalVisits === 0) {
            return [];
        }

        /** @var array<int, array{domain: string, visits: int, percentage: float}> */
        return collect($referrers)->map(function (array $referrer) use ($totalVisits): array {
            $percentage = round(($referrer['visits'] / $totalVisits) * 100, 1);

            return [
                'domain' => $referrer['domain'] ?: '(direct)',
                'visits' => $referrer['visits'],
                'percentage' => $percentage,
            ];
        })->toArray();
    }

    /**
     * @param  Builder<RequestAnalytics>  $query
     * @return array<int, array{browser: string, count: int, percentage?: float}>
     */
    public function getBrowsersData(Builder $query, bool $withPercentages): array
    {
        /* @var array<int, array{browser: string, count: int}> $browsers */
        $browsers = (clone $query)
            ->select('browser', DB::raw('COUNT(*) as count'))
            ->whereNotNull('browser')
            ->groupBy('browser')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get()
            ->toArray();

        if (! $withPercentages) {
            return $browsers;
        }

        $totalCount = array_sum(array_column($browsers, 'count'));
        if ($totalCount === 0) {
            return [];
        }

        /** @var array<int, array{browser: string, count: int, percentage: float}> */
        return collect($browsers)->map(function (array $browser) use ($totalCount): array {
            $percentage = round(($browser['count'] / $totalCount) * 100, 1);

            return [
                'browser' => $browser['browser'],
                'count' => $browser['count'],
                'percentage' => $percentage,
            ];
        })->toArray();
    }

    /**
     * @param  Builder<RequestAnalytics>  $query
     * @return array<int, array{name: string, count: int, percentage?: float}>
     */
    public function getDevices(Builder $query, bool $withPercentages = false): array
    {
        /* @var array<int, array{device: string, count: int}> $devices */
        $devices = (clone $query)
            ->select('device', DB::raw('COUNT(*) as count'))
            ->whereNotNull('device')
            ->groupBy('device')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get()
            ->toArray();

        if (! $withPercentages) {
            return $devices;
        }

        $totalCount = array_sum(array_column($devices, 'count'));
        if ($totalCount === 0) {
            return [];
        }

        /** @var array<int, array{name: string, count: int, percentage: float}> */
        return collect($devices)->map(function (array $device) use ($totalCount): array {
            $percentage = round(($device['count'] / $totalCount) * 100, 1);

            return [
                'name' => $device['device'],
                'count' => $device['count'],
                'percentage' => $percentage,
            ];
        })->toArray();
    }

    /**
     * @param  Builder<RequestAnalytics>  $query
     * @return array<int, array{name: string, count: int, percentage?: float, code?: string}>
     */
    public function getCountriesData(Builder $query, bool $withPercentages): array
    {
        /* @var array<int, array{country: string, count: int}> $countries */
        $countries = (clone $query)
            ->select('country', DB::raw('COUNT(*) as count'))
            ->whereNotNull('country')
            ->where('country', '!=', '')
            ->groupBy('country')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get()
            ->toArray();

        if (! $withPercentages) {
            return $countries;
        }

        $totalCount = array_sum(array_column($countries, 'count'));
        if ($totalCount === 0) {
            return [];
        }

        /** @var array<int, array{name: string, count: int, percentage: int, code: string}> */
        return collect($countries)->map(function (array $country) use ($totalCount): array {
            $percentage = round(($country['count'] / $totalCount) * 100, 1);

            return [
                'name' => $country['country'],
                'count' => $country['count'],
                'percentage' => $percentage,
                'code' => $this->getCountryCode($country['country']),
            ];
        })->toArray();
    }

    /**
     * @param  Builder<RequestAnalytics>  $query
     * @return array{name: string, count: int, percentage?: float}[]
     */
    public function getOperatingSystems(Builder $query, bool $withPercentages = false): array
    {
        /* @var int $totalVisitors */
        $totalVisitors = (clone $query)->distinct('session_id')->count('session_id');

        if ($totalVisitors === 0) {
            return [];
        }
        /* @var array<int, array{name: string, count: int}> $operatingSystems */
        $operatingSystems = (clone $query)
            ->select('operating_system as name', DB::raw('COUNT(DISTINCT session_id) as count'))
            ->whereNotNull('operating_system')
            ->groupBy('operating_system')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get()
            ->toArray();

        if (! $withPercentages) {
            return $operatingSystems;
        }

        /** @var array<int, array<string, mixed>> */
        return collect($operatingSystems)->map(function (array $os) use ($totalVisitors): array {
            $percentage = round(($os['count'] / $totalVisitors) * 100, 1);

            return [
                'name' => $os['name'],
                'count' => $os['count'],
                'percentage' => $percentage,
            ];
        })->toArray();
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array{
     *     summary: array<string, mixed>,
     *     chart: array<string, mixed>,
     *     top_pages: array<int, mixed>,
     *     top_referrers: array<int, mixed>,
     *     browsers: array<int, mixed>,
     *     devices: array<int, mixed>,
     *     countries: array<int, mixed>,
     *     operating_systems: array<int, mixed>
     * }
     */
    public function getOverviewData(array $params): array
    {
        $dateRange = $this->getDateRange($params);
        $requestCategory = $params['request_category'] ?? null;
        $query = $this->getBaseQuery($dateRange, $requestCategory);
        $withPercentages = (bool) ($params['with_percentages'] ?? false);

        return [
            'summary' => $this->getSummary($query, $dateRange),
            'chart' => $this->getChartData($query, $dateRange),
            'top_pages' => $this->getTopPages($query, $withPercentages),
            'top_referrers' => $this->getTopReferrers($query, $withPercentages),
            'browsers' => $this->getBrowsersData($query, $withPercentages),
            'devices' => $this->getDevices($query, $withPercentages),
            'countries' => $this->getCountriesData($query, $withPercentages),
            'operating_systems' => $this->getOperatingSystems($query, $withPercentages),
        ];
    }

    /**
     * @param  array<string, mixed>  $params
     * @return LengthAwarePaginator<int, RequestAnalytics>
     */
    public function getVisitors(array $params, int $perPage = 50)
    {
        $dateRange = $this->getDateRange($params);
        $requestCategory = $params['request_category'] ?? null;

        return $this->getBaseQuery($dateRange, $requestCategory)
            ->select(
                'visitor_id',
                DB::raw('COUNT(*) as page_views'),
                DB::raw('COUNT(DISTINCT session_id) as sessions'),
                DB::raw('MIN(visited_at) as first_visit'),
                DB::raw('MAX(visited_at) as last_visit'),
                DB::raw('COUNT(DISTINCT path) as unique_pages')
            )
            ->whereNotNull('visitor_id')
            ->groupBy('visitor_id')
            ->orderBy('last_visit', 'desc')
            ->paginate($perPage);
    }

    /**
     * @param  array<string, mixed>  $params
     * @return LengthAwarePaginator<int, RequestAnalytics>
     */
    public function getPageViews(array $params, int $perPage = 50): LengthAwarePaginator
    {
        $dateRange = $this->getDateRange($params);
        $requestCategory = $params['request_category'] ?? null;
        $query = $this->getBaseQuery($dateRange, $requestCategory);

        if (isset($params['path'])) {
            $query->where('path', 'like', "%{$params['path']}%");
        }

        return $query
            ->select('*')
            ->orderBy('visited_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * @param  Builder<RequestAnalytics>  $query
     */
    public function getUniqueVisitorCount(Builder $query): int
    {
        return (clone $query)
            ->select(DB::raw($this->getUniqueVisitorCountExpression()))
            ->value('unique_visitor_count') ?? 0;
    }

    public function getUniqueVisitorCountExpression(): string
    {
        return 'COUNT(DISTINCT session_id) as unique_visitor_count';
    }

    public function getDomainExpression(string $column): string
    {
        $connection = config('request-analytics.database.connection');
        $driver = DB::connection($connection)->getDriverName();

        return match ($driver) {
            'mysql' => "SUBSTRING_INDEX(SUBSTRING_INDEX({$column}, '/', 3), '//', -1)",
            'pgsql' => "SPLIT_PART(SPLIT_PART({$column}, '/', 3), '//', 2)",
            'sqlite' => "CASE
                WHEN {$column} LIKE '%://%' THEN
                    REPLACE(
                        REPLACE(
                            SUBSTR({$column}, INSTR({$column}, '://') + 3),
                            SUBSTR(SUBSTR({$column}, INSTR({$column}, '://') + 3), INSTR(SUBSTR({$column}, INSTR({$column}, '://') + 3), '/'))
                            , ''
                        ),
                        'www.', ''
                    )
                ELSE {$column}
                END",
            default => "SUBSTRING_INDEX(SUBSTRING_INDEX({$column}, '/', 3), '//', -1)"
        };
    }

    public function getDurationExpression(string $column): string
    {
        $connection = config('request-analytics.database.connection');
        $driver = DB::connection($connection)->getDriverName();

        return match ($driver) {
            'mysql' => "TIMESTAMPDIFF(SECOND, MIN({$column}), MAX({$column}))",
            'pgsql' => "EXTRACT(EPOCH FROM (MAX({$column}) - MIN({$column})))",
            'sqlite' => "CAST((julianday(MAX({$column})) - julianday(MIN({$column}))) * 86400 AS INTEGER)",
            default => "TIMESTAMPDIFF(SECOND, MIN({$column}), MAX({$column}))"
        };
    }

    protected function getCountryCode(string $countryName): string
    {
        $countryMap = [
            'Afghanistan' => 'af',
            'Albania' => 'al',
            'Algeria' => 'dz',
            'Argentina' => 'ar',
            'Australia' => 'au',
            'Austria' => 'at',
            'Bangladesh' => 'bd',
            'Belgium' => 'be',
            'Brazil' => 'br',
            'Bulgaria' => 'bg',
            'Canada' => 'ca',
            'Chile' => 'cl',
            'China' => 'cn',
            'Colombia' => 'co',
            'Croatia' => 'hr',
            'Czech Republic' => 'cz',
            'Denmark' => 'dk',
            'Egypt' => 'eg',
            'Finland' => 'fi',
            'France' => 'fr',
            'Germany' => 'de',
            'Greece' => 'gr',
            'Hungary' => 'hu',
            'Iceland' => 'is',
            'India' => 'in',
            'Indonesia' => 'id',
            'Iran' => 'ir',
            'Iraq' => 'iq',
            'Ireland' => 'ie',
            'Israel' => 'il',
            'Italy' => 'it',
            'Japan' => 'jp',
            'Jordan' => 'jo',
            'Kenya' => 'ke',
            'Malaysia' => 'my',
            'Mexico' => 'mx',
            'Netherlands' => 'nl',
            'New Zealand' => 'nz',
            'Nigeria' => 'ng',
            'Norway' => 'no',
            'Pakistan' => 'pk',
            'Philippines' => 'ph',
            'Poland' => 'pl',
            'Portugal' => 'pt',
            'Romania' => 'ro',
            'Russia' => 'ru',
            'Saudi Arabia' => 'sa',
            'Singapore' => 'sg',
            'Slovakia' => 'sk',
            'Slovenia' => 'si',
            'South Africa' => 'za',
            'South Korea' => 'kr',
            'Spain' => 'es',
            'Sweden' => 'se',
            'Switzerland' => 'ch',
            'Thailand' => 'th',
            'Turkey' => 'tr',
            'Ukraine' => 'ua',
            'United Arab Emirates' => 'ae',
            'United Kingdom' => 'gb',
            'United States' => 'us',
            'Vietnam' => 'vn',
        ];

        return $countryMap[$countryName] ?? strtolower(substr($countryName, 0, 2));
    }
}
