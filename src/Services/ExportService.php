<?php

namespace MeShaon\RequestAnalytics\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Response;
use League\Csv\Writer;
use MeShaon\RequestAnalytics\Models\RequestAnalytics;

class ExportService
{
    public function exportToCsv(Collection $data, array $columns = []): string
    {
        $csv = Writer::createFromString('');

        // Add headers
        if (empty($columns) && $data->isNotEmpty()) {
            $columns = array_keys($data->first()->toArray());
        }
        $csv->insertOne($columns);

        // Add data rows
        foreach ($data as $row) {
            $csvRow = [];
            foreach ($columns as $column) {
                $csvRow[] = data_get($row, $column, '');
            }
            $csv->insertOne($csvRow);
        }

        return $csv->toString();
    }

    public function exportToJson(Collection $data): string
    {
        return $data->toJson(JSON_PRETTY_PRINT);
    }

    public function getAnalyticsData(array $filters = []): Collection
    {
        $query = RequestAnalytics::query();

        if (isset($filters['start_date'])) {
            $query->where('visited_at', '>=', $filters['start_date']);
        }

        if (isset($filters['end_date'])) {
            $query->where('visited_at', '<=', $filters['end_date']);
        }

        if (isset($filters['path'])) {
            $query->where('path', 'like', '%'.$filters['path'].'%');
        }

        if (isset($filters['country'])) {
            $query->where('country', $filters['country']);
        }

        if (isset($filters['browser'])) {
            $query->where('browser', $filters['browser']);
        }

        if (isset($filters['device'])) {
            $query->where('device', $filters['device']);
        }

        return $query->get();
    }

    public function downloadCsv(Collection $data, string $filename = 'analytics_export.csv')
    {
        $csv = $this->exportToCsv($data);

        return Response::make($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    public function downloadJson(Collection $data, string $filename = 'analytics_export.json')
    {
        $json = $this->exportToJson($data);

        return Response::make($json, 200, [
            'Content-Type' => 'application/json',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }
}
