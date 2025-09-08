<?php

declare(strict_types=1);

namespace MeShaon\RequestAnalytics\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use MeShaon\RequestAnalytics\Services\DashboardAnalyticsService;

class RequestAnalyticsController extends BaseController
{
    public function __construct(protected DashboardAnalyticsService $dashboardService) {}

    public function show(Request $request)
    {
        $dateRangeInput = $request->input('date_range', 30);
        $dateRange = is_numeric($dateRangeInput) && (int) $dateRangeInput > 0
            ? (int) $dateRangeInput
            : 30;

        $params = [
            'date_range' => $dateRange,
            'request_category' => $request->input('request_category', null),
        ];

        $data = $this->dashboardService->getDashboardData($params);

        return view('request-analytics::analytics', $data);
    }
}
