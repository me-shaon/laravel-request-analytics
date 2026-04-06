<?php

declare(strict_types=1);

namespace MeShaon\RequestAnalytics\Exceptions;

/**
 * Exception thrown when bot detection fails
 */
class BotDetectionException extends RequestAnalyticsException
{
    public function __construct(
        protected ?string $userAgent = null,
        protected ?string $ipAddress = null,
        string $message = '',
        int|string $code = 0,
        ?\Throwable $previous = null
    ) {
        $message = $message ?: 'Bot detection failed';
        parent::__construct($message, (int) $code, $previous);
    }

    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    public function getIpAddress(): ?string
    {
        return $this->ipAddress;
    }
}
