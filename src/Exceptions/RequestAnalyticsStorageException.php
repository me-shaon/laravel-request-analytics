<?php

namespace MeShaon\RequestAnalytics\Exceptions;

/**
 * Exception thrown when storing request analytics data fails
 */
class RequestAnalyticsStorageException extends RequestAnalyticsException
{
    /**
     * @param  array<string, mixed>  $requestData
     */
    public function __construct(
        protected array $requestData = [],
        string $message = '',
        int|string $code = 0,
        ?\Throwable $previous = null
    ) {
        $message = $message ?: 'Failed to store request analytics data';
        parent::__construct($message, (int) $code, $previous);
    }

    /**
     * @return array<string, mixed>
     */
    public function getRequestData(): array
    {
        return $this->requestData;
    }
}
