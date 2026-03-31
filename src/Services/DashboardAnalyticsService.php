<?php

declare(strict_types=1);

namespace MeShaon\RequestAnalytics\Services;

use Illuminate\Database\Eloquent\Builder;
use MeShaon\RequestAnalytics\Models\RequestAnalytics;

class DashboardAnalyticsService
{
    public function __construct(protected AnalyticsService $analyticsService) {}

    /**
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    public function getDashboardData(array $params): array
    {
        $dateRange = $this->analyticsService->getDateRange($params);
        $query = $this->analyticsService->getBaseQuery($dateRange, $params['request_category']);
        $chartData = $this->getChartData($query, $dateRange);

        return [
            'browsers' => $this->analyticsService->getBrowsersData($query, true),
            'operatingSystems' => $this->analyticsService->getOperatingSystems($query, true),
            'devices' => $this->analyticsService->getDevices($query, true),
            'pages' => $this->analyticsService->getTopPages($query, true),
            'referrers' => $this->analyticsService->getTopReferrers($query, true),
            'labels' => $chartData['labels'],
            'datasets' => $chartData['datasets'],
            'average' => $this->analyticsService->getSummary($query, $dateRange),
            'countries' => $this->analyticsService->getCountriesData($query, true),
            'dateRange' => $params['date_range'] ?? $dateRange['days'],
        ];
    }

    /**
     * @param  Builder<RequestAnalytics>  $query
     * @param  array<string, mixed>  $dateRange
     * @return array{labels: array<int, string>, datasets: array<int, array<string, mixed>>}
     */
    protected function getChartData(Builder $query, array $dateRange): array
    {
        $chartData = $this->analyticsService->getChartData($query, $dateRange);

        /** @var array<int, array<string, mixed>> $datasets */
        $datasets = $chartData['datasets'];
        $chartData['datasets'] = collect($datasets)->map(function (array $dataset): array {
            if ($dataset['label'] === 'Views') {
                return array_merge($dataset, [
                    'backgroundColor' => 'rgba(220, 38, 127, 0.1)', // Bright pink background
                    'borderColor' => 'rgba(220, 38, 127, 1)', // Bright pink border
                    'borderWidth' => 4,
                    'fill' => false,
                    'tension' => 0.3,
                    'pointBackgroundColor' => 'rgba(220, 38, 127, 1)',
                    'pointBorderColor' => '#fff',
                    'pointBorderWidth' => 3,
                    'pointRadius' => 6,
                    'pointHoverRadius' => 8,
                    'borderDash' => [], // Solid line
                ]);
            }
            if ($dataset['label'] === 'Visitors') {
                return array_merge($dataset, [
                    'backgroundColor' => 'rgba(34, 197, 94, 0.1)', // Bright green background
                    'borderColor' => 'rgba(34, 197, 94, 1)', // Bright green border
                    'borderWidth' => 4,
                    'fill' => false,
                    'tension' => 0.3,
                    'pointBackgroundColor' => 'rgba(34, 197, 94, 1)',
                    'pointBorderColor' => '#fff',
                    'pointBorderWidth' => 3,
                    'pointRadius' => 6,
                    'pointHoverRadius' => 8,
                    'borderDash' => [10, 5], // Dashed line to differentiate
                ]);
            }

            return $dataset;
        })->toArray();

        return $chartData;
    }
}
