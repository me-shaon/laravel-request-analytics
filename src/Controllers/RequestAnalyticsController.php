<?php

namespace MeShaon\RequestAnalytics\Controllers;

use Carbon\CarbonInterval;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use MeShaon\RequestAnalytics\Models\RequestAnalytics;

class RequestAnalyticsController extends BaseController
{
    protected $dateRange;

    public function __construct(Request $request)
    {
        $this->dateRange = $request->input('date_range', 30);
    }

    public function show(Request $request)
    {
        $chartData = $this->getChartData();

        return view('request-analytics::analytics', [
            'browsers' => $this->getBrowsers(),
            'operatingSystems' => $this->getOperatingSystems(),
            'devices' => $this->getDevices(),
            'pages' => $this->getPages(),
            'referrers' => $this->getReferrers(),
            'labels' => $chartData['labels'],
            'datasets' => $chartData['datasets'],
            'average' => $this->getAverage(),
            'countries' => $this->getCountries(),
            'dateRange' => $this->dateRange,
        ]);
    }

    private function getBaseQuery()
    {
        $startDate = Carbon::now()->subDays($this->dateRange)->startOfDay();

        return RequestAnalytics::where('visited_at', '>=', $startDate);
    }

    private function getCountries(): array
    {
        $cacheKey = "analytics_countries_{$this->dateRange}";

        return Cache::remember($cacheKey, now()->addMinutes(5), function () {
            $totalVisitors = $this->getBaseQuery()->count();

            if ($totalVisitors === 0) {
                return [];
            }

            $countries = $this->getBaseQuery()
                ->select('country as name', DB::raw('LOWER(country) as code'), DB::raw('COUNT(DISTINCT session_id) as visitorCount'))
                ->whereNotNull('country')
                ->where('country', '!=', '')
                ->groupBy('country')
                ->orderBy('visitorCount', 'desc')
                ->limit(10)
                ->get();

            return $countries->map(function ($country) use ($totalVisitors) {
                $percentage = round(($country->visitorCount / $totalVisitors) * 100, 1);

                return [
                    'name' => $country->name,
                    'visitorCount' => $country->visitorCount,
                    'percentage' => $percentage,
                    'code' => strtolower($country->code),
                ];
            })->toArray();
        });
    }

    private function getBrowsers(): array
    {
        $cacheKey = "analytics_browsers_{$this->dateRange}";

        return Cache::remember($cacheKey, now()->addMinutes(5), function () {
            $totalVisitors = $this->getBaseQuery()->count();

            if ($totalVisitors === 0) {
                return [];
            }

            $browsers = $this->getBaseQuery()
                ->select('browser', DB::raw('COUNT(DISTINCT session_id) as visitorCount'))
                ->whereNotNull('browser')
                ->groupBy('browser')
                ->orderBy('visitorCount', 'desc')
                ->limit(10)
                ->get();

            return $browsers->map(function ($browser) use ($totalVisitors) {
                $percentage = round(($browser->visitorCount / $totalVisitors) * 100, 1);

                return [
                    'browser' => $browser->browser,
                    'visitorCount' => $browser->visitorCount,
                    'percentage' => $percentage,
                ];
            })->toArray();
        });
    }

    private function getOperatingSystems(): array
    {
        $totalVisitors = $this->getBaseQuery()->count();

        if ($totalVisitors === 0) {
            return [];
        }

        $operatingSystems = $this->getBaseQuery()
            ->select('operating_system as name', DB::raw('COUNT(DISTINCT session_id) as visitorCount'))
            ->whereNotNull('operating_system')
            ->groupBy('operating_system')
            ->orderBy('visitorCount', 'desc')
            ->limit(10)
            ->get();

        return $operatingSystems->map(function ($os) use ($totalVisitors) {
            $percentage = round(($os->visitorCount / $totalVisitors) * 100, 1);

            return [
                'name' => $os->name,
                'visitorCount' => $os->visitorCount,
                'percentage' => $percentage,
            ];
        })->toArray();
    }

    private function getDevices(): array
    {
        $totalVisitors = $this->getBaseQuery()->count();

        if ($totalVisitors === 0) {
            return [];
        }

        $devices = $this->getBaseQuery()
            ->select('device as name', DB::raw('COUNT(DISTINCT session_id) as visitorCount'))
            ->whereNotNull('device')
            ->groupBy('device')
            ->orderBy('visitorCount', 'desc')
            ->limit(10)
            ->get();

        return $devices->map(function ($device) use ($totalVisitors) {
            $percentage = round(($device->visitorCount / $totalVisitors) * 100, 1);

            return [
                'name' => $device->name,
                'visitorCount' => $device->visitorCount,
                'percentage' => $percentage,
            ];
        })->toArray();
    }

    private function getPages(): array
    {
        $totalPageViews = $this->getBaseQuery()->count();

        if ($totalPageViews === 0) {
            return [];
        }

        $pages = $this->getBaseQuery()
            ->select('path', DB::raw('COUNT(*) as visitorCount'))
            ->groupBy('path')
            ->orderBy('visitorCount', 'desc')
            ->limit(10)
            ->get();

        return $pages->map(function ($page) use ($totalPageViews) {
            $percentage = round(($page->visitorCount / $totalPageViews) * 100, 1);

            return [
                'path' => $page->path,
                'visitorCount' => $page->visitorCount,
                'percentage' => $percentage,
            ];
        })->toArray();
    }

    private function getReferrers()
    {
        $totalVisitors = $this->getBaseQuery()->count();

        if ($totalVisitors === 0) {
            return [];
        }

        $referrers = $this->getBaseQuery()
            ->select(
                DB::raw('SUBSTRING_INDEX(SUBSTRING_INDEX(referrer, "/", 3), "//", -1) as domain'),
                DB::raw('COUNT(DISTINCT session_id) as visitorCount')
            )
            ->whereNotNull('referrer')
            ->where('referrer', '!=', '')
            ->groupBy('domain')
            ->orderBy('visitorCount', 'desc')
            ->limit(10)
            ->get();

        return $referrers->map(function ($referrer) use ($totalVisitors) {
            $percentage = round(($referrer->visitorCount / $totalVisitors) * 100, 1);

            return [
                'domain' => $referrer->domain ?: '(direct)',
                'visitorCount' => $referrer->visitorCount,
                'percentage' => $percentage,
            ];
        })->toArray();
    }

    private function getChartData(): array
    {
        $days = $this->dateRange;
        $startDate = Carbon::now()->subDays($days)->startOfDay();

        $dateRange = collect(range(0, $days))
            ->mapWithKeys(function ($day) {
                $date = Carbon::now()->subDays($day)->format('Y-m-d');

                return [
                    $date => [
                        'date' => $date,
                        'views' => 0,
                        'visitors' => 0,
                    ],
                ];
            });

        $dailyData = $this->getBaseQuery()
            ->select(
                DB::raw('DATE(visited_at) as date'),
                DB::raw('COUNT(*) as views'),
                DB::raw('COUNT(DISTINCT session_id) as visitors')
            )
            ->where('visited_at', '>=', $startDate)
            ->groupBy(DB::raw('DATE(visited_at)'))
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        $mergedData = $dateRange->map(function ($item, $date) use ($dailyData) {
            return $dailyData->has($date)
                ? array_merge($item, $dailyData->get($date)->toArray())
                : $item;
        })->sortKeys();

        $datasets = [
            [
                'label' => 'Views',
                'data' => $mergedData->pluck('views')->toArray(),
                'backgroundColor' => 'rgba(255, 99, 132, 0.2)',
                'borderColor' => 'rgba(255, 99, 132, 1)',
                'borderWidth' => 1,
            ],
            [
                'label' => 'Visitors',
                'data' => $mergedData->pluck('visitors')->toArray(),
                'backgroundColor' => 'rgba(54, 162, 235, 0.2)',
                'borderColor' => 'rgba(54, 162, 235, 1)',
                'borderWidth' => 1,
            ],
        ];

        return [
            'labels' => $mergedData->keys()->map(function ($date) {
                return Carbon::parse($date)->format('M d');
            })->toArray(),
            'datasets' => $datasets,
        ];
    }

    private function getAverage()
    {
        $startDate = Carbon::now()->subDays($this->dateRange)->startOfDay();

        $totalViews = $this->getBaseQuery()->count();

        $uniqueVisitors = $this->getBaseQuery()
            ->distinct('session_id')
            ->count('session_id');

        // Calculate bounce rate (percentage of sessions with only one page view)
        $sessionsWithSinglePageView = DB::table(function ($query) use ($startDate) {
            $query->from('request_analytics')
                ->select('session_id')
                ->where('visited_at', '>=', $startDate)
                ->groupBy('session_id')
                ->havingRaw('COUNT(*) = 1');
        }, 'single_page_sessions')->count();

        $bounceRate = $uniqueVisitors > 0
            ? round(($sessionsWithSinglePageView / $uniqueVisitors) * 100, 1)
            : 0;

        // Calculate average visit time (in seconds)
        // This is an approximation based on the difference between first and last hit for each session
        $sessionTimes = $this->getBaseQuery()
            ->select(
                'session_id',
                DB::raw('TIMESTAMPDIFF(SECOND, MIN(visited_at), MAX(visited_at)) as duration')
            )
            ->groupBy('session_id')
            ->having('duration', '>', 0)
            ->pluck('duration')
            ->toArray();

        $avgVisitTime = count($sessionTimes) > 0
            ? round(array_sum($sessionTimes) / count($sessionTimes), 1)
            : 0;

        return [
            'views' => $totalViews,
            'visitors' => $uniqueVisitors,
            'bounce-rate' => $bounceRate.'%',
            'average-visit-time' => $this->formatTimeWithCarbon($avgVisitTime),
        ];
    }

    /**
     * Format seconds into a human-readable duration using Carbon
     *
     * @param  int  $seconds
     * @return string
     */
    private function formatTimeWithCarbon($seconds)
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
}
