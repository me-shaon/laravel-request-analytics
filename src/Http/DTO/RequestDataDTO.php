<?php

namespace MeShaon\RequestAnalytics\Http\DTO;

class RequestDataDTO
{
    public function __construct(
        public string $url,
        public string $content,
        public array $browserInfo,
        public string $ipAddress,
        public string $referrer,
        public string $country,
        public string $language,
        public string $queryParams,
        public string $httpMethod,
        public string $responseTime
    ) {}
}
