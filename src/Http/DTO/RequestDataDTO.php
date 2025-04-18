<?php

namespace MeShaon\RequestAnalytics\Http\DTO;

class RequestDataDTO
{
    public function __construct(
        public string $path,
        public string $content,
        public array $browserInfo,
        public string $ipAddress,
        public string $referrer,
        public string $country,
        public string $language,
        public string $queryParams,
        public string $httpMethod,
        public int $responseTime,
        public string $requestCategory
    ) {}
}
