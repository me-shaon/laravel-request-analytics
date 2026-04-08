<?php

declare(strict_types=1);

namespace MeShaon\RequestAnalytics\Exceptions;

/**
 * Exception thrown when a geolocation provider (API) fails
 */
class GeolocationProviderException extends GeolocationException
{
    public function __construct(
        protected string $provider,
        protected string $ipAddress,
        string $message = '',
        int|string $code = 0,
        ?\Throwable $previous = null
    ) {
        $message = $message ?: "Geolocation lookup failed for IP {$this->ipAddress} using provider {$this->provider}";
        parent::__construct($message, (int) $code, $previous);
    }

    public function getProvider(): string
    {
        return $this->provider;
    }

    public function getIpAddress(): string
    {
        return $this->ipAddress;
    }
}
