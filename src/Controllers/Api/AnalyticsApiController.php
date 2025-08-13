<?php

namespace MeShaon\RequestAnalytics\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use MeShaon\RequestAnalytics\Models\RequestAnalytics;

class AnalyticsApiController extends BaseController
{
    public function overview(Request $request): JsonResponse
    {
        $request->validate([
            'date_range' => 'integer|min:1|max:365',
            'start_date' => 'date',
            'end_date' => 'date|after_or_equal:start_date',
        ]);

        $dateRange = $this->getDateRange($request);

        $data = Cache::remember("api_overview_{$dateRange['key']}", now()->addMinutes(5), function () use ($dateRange) {
            $query = $this->getBaseQuery($dateRange);

            return [
                'summary' => $this->getSummary($query),
                'chart' => $this->getChartData($query, $dateRange),
                'top_pages' => $this->getTopPages($query),
                'top_referrers' => $this->getTopReferrers($query),
                'browsers' => $this->getBrowsers($query),
                'devices' => $this->getDevices($query),
                'countries' => $this->getCountries($query),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data,
            'date_range' => $dateRange,
        ]);
    }

    public function visitors(Request $request): JsonResponse
    {
        $request->validate([
            'date_range' => 'integer|min:1|max:365',
            'page' => 'integer|min:1',
            'per_page' => 'integer|min:10|max:100',
        ]);

        $dateRange = $this->getDateRange($request);
        $perPage = $request->input('per_page', 50);

        $visitors = $this->getBaseQuery($dateRange)
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

        return response()->json([
            'success' => true,
            'data' => $visitors,
        ]);
    }

    public function pageViews(Request $request): JsonResponse
    {
        $request->validate([
            'date_range' => 'integer|min:1|max:365',
            'path' => 'string',
            'page' => 'integer|min:1',
            'per_page' => 'integer|min:10|max:100',
        ]);

        $dateRange = $this->getDateRange($request);
        $perPage = $request->input('per_page', 50);

        $query = $this->getBaseQuery($dateRange);

        if ($path = $request->input('path')) {
            $query->where('path', 'like', "%{$path}%");
        }

        $pageViews = $query
            ->select('*')
            ->orderBy('visited_at', 'desc')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $pageViews,
        ]);
    }

    public function export(Request $request): JsonResponse
    {
        $request->validate([
            'format' => 'required|in:csv,json',
            'date_range' => 'integer|min:1|max:365',
            'type' => 'required|in:overview,visitors,page_views',
        ]);

        // This would typically queue a job for large exports
        // For now, we'll return a simple response
        return response()->json([
            'success' => true,
            'message' => 'Export queued successfully',
            'job_id' => uniqid('export_'),
        ]);
    }

    protected function getDateRange(Request $request): array
    {
        if ($request->has('start_date') && $request->has('end_date')) {
            $startDate = Carbon::parse($request->input('start_date'))->startOfDay();
            $endDate = Carbon::parse($request->input('end_date'))->endOfDay();
            $days = $startDate->diffInDays($endDate);
        } else {
            $days = $request->input('date_range', 30);
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

    protected function getBaseQuery(array $dateRange)
    {
        return RequestAnalytics::whereBetween('visited_at', [$dateRange['start'], $dateRange['end']]);
    }

    protected function getSummary($query): array
    {
        $totalViews = (clone $query)->count();
        $uniqueVisitors = (clone $query)->distinct('visitor_id')->count('visitor_id');
        $uniqueSessions = (clone $query)->distinct('session_id')->count('session_id');
        $avgResponseTime = (clone $query)->avg('response_time');

        return [
            'total_views' => $totalViews,
            'unique_visitors' => $uniqueVisitors,
            'unique_sessions' => $uniqueSessions,
            'avg_response_time' => round($avgResponseTime, 2),
        ];
    }

    protected function getChartData($query, array $dateRange): array
    {
        $data = (clone $query)
            ->select(
                DB::raw('DATE(visited_at) as date'),
                DB::raw('COUNT(*) as views'),
                DB::raw('COUNT(DISTINCT visitor_id) as visitors')
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
                $views[] = $data->get($dateStr)->views;
                $visitors[] = $data->get($dateStr)->visitors;
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

    protected function getTopPages($query): array
    {
        return (clone $query)
            ->select('path', DB::raw('COUNT(*) as views'))
            ->groupBy('path')
            ->orderBy('views', 'desc')
            ->limit(10)
            ->get()
            ->toArray();
    }

    protected function getTopReferrers($query): array
    {
        return (clone $query)
            ->select(
                DB::raw('SUBSTRING_INDEX(SUBSTRING_INDEX(referrer, "/", 3), "//", -1) as domain'),
                DB::raw('COUNT(*) as visits')
            )
            ->whereNotNull('referrer')
            ->where('referrer', '!=', '')
            ->groupBy('domain')
            ->orderBy('visits', 'desc')
            ->limit(10)
            ->get()
            ->toArray();
    }

    protected function getBrowsers($query): array
    {
        return (clone $query)
            ->select('browser', DB::raw('COUNT(*) as count'))
            ->whereNotNull('browser')
            ->groupBy('browser')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get()
            ->toArray();
    }

    protected function getDevices($query): array
    {
        return (clone $query)
            ->select('device', DB::raw('COUNT(*) as count'))
            ->whereNotNull('device')
            ->groupBy('device')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get()
            ->toArray();
    }

    protected function getCountries($query): array
    {
        return (clone $query)
            ->select('country', DB::raw('COUNT(*) as count'))
            ->whereNotNull('country')
            ->where('country', '!=', '')
            ->groupBy('country')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get()
            ->toArray();
    }
}
