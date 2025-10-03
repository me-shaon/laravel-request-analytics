<?php

declare(strict_types=1);

namespace MeShaon\RequestAnalytics\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use MeShaon\RequestAnalytics\Services\DashboardAnalyticsService;

class RequestAnalyticsController extends BaseController
{
    public function __construct(protected DashboardAnalyticsService $dashboardService) {}

    public function show(Request $request)
    {
        $params = [];

        if ($request->has('start_date') && $request->has('end_date')) {
            $params['start_date'] = $request->input('start_date');
            $params['end_date'] = $request->input('end_date');
        } else {
            $dateRangeInput = $request->input('date_range', 30);
            $dateRange = is_numeric($dateRangeInput) && (int) $dateRangeInput > 0
                ? (int) $dateRangeInput
                : 30;
            $params['date_range'] = $dateRange;
        }

        $params['request_category'] = $request->input('request_category', null);

        $data = $this->dashboardService->getDashboardData($params);

        return view('request-analytics::analytics', $data);
    }
}
