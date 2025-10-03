<?php

declare(strict_types=1);

namespace MeShaon\RequestAnalytics\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Cache;
use MeShaon\RequestAnalytics\Http\Requests\OverviewRequest;
use MeShaon\RequestAnalytics\Http\Requests\PageViewsRequest;
use MeShaon\RequestAnalytics\Http\Requests\VisitorsRequest;
use MeShaon\RequestAnalytics\Services\AnalyticsService;

class AnalyticsApiController extends BaseController
{
    public function __construct(protected AnalyticsService $analyticsService) {}

    public function overview(OverviewRequest $request): JsonResponse
    {
        $params = $request->validated();
        $dateRange = $this->analyticsService->getDateRange($params);

        $data = Cache::remember("api_overview_{$dateRange['key']}", now()->addMinutes(5), fn (): array => $this->analyticsService->getOverviewData($params));

        return response()->json([
            'data' => $data,
            'date_range' => $dateRange,
        ]);
    }

    public function visitors(VisitorsRequest $request): JsonResponse
    {
        $params = $request->validated();
        $perPage = (int) $request->input('per_page', 50);

        $visitors = $this->analyticsService->getVisitors($params, $perPage);

        return response()->json([
            'data' => $visitors,
        ]);
    }

    public function pageViews(PageViewsRequest $request): JsonResponse
    {
        $params = $request->validated();
        $perPage = (int) $request->input('per_page', 50);

        $pageViews = $this->analyticsService->getPageViews($params, $perPage);

        return response()->json([
            'data' => $pageViews,
        ]);
    }
}
