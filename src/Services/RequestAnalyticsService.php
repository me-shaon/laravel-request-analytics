<?php

namespace MeShaon\RequestAnalytics\Services;

use Illuminate\Support\Facades\Auth;
use MeShaon\RequestAnalytics\DTO\RequestDataDTO;
use MeShaon\RequestAnalytics\Exceptions\RequestAnalyticsStorageException;
use MeShaon\RequestAnalytics\Models\RequestAnalytics;

class RequestAnalyticsService
{
    public function store(RequestDataDTO $requestDataDTO)
    {
        $requestData = [
            'path' => $requestDataDTO->path,
            'page_title' => $this->extractPageTitle($requestDataDTO->content),
            'ip_address' => $requestDataDTO->ipAddress,
            'operating_system' => $requestDataDTO->browserInfo['operating_system'],
            'browser' => $requestDataDTO->browserInfo['browser'],
            'device' => $requestDataDTO->browserInfo['device'],
            'screen' => '',
            'referrer' => $requestDataDTO->referrer,
            'country' => $requestDataDTO->country,
            'city' => $requestDataDTO->city,
            'language' => $requestDataDTO->language,
            'query_params' => $requestDataDTO->queryParams,
            'session_id' => $requestDataDTO->sessionId ?: session()->getId(),
            'visitor_id' => $requestDataDTO->visitorId,
            'user_id' => Auth::id(),
            'http_method' => $requestDataDTO->httpMethod,
            'request_category' => $requestDataDTO->requestCategory,
            'response_time' => round($requestDataDTO->responseTime * 1000), // Convert to milliseconds
            'visited_at' => now(),
        ];

        try {
            return RequestAnalytics::create($requestData);
        } catch (\Exception $e) {
            throw new RequestAnalyticsStorageException(
                $requestData,
                'Failed to store request analytics data: '.$e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }

    private function extractPageTitle(string $content): string
    {
        $matches = [];
        preg_match('/<title>(.*?)<\/title>/i', $content, $matches);

        return $matches[1] ?? '';
    }
}
