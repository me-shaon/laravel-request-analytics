<?php

declare(strict_types=1);

namespace MeShaon\RequestAnalytics\Exceptions;

use Exception;
use Throwable;

class AddressNotFoundException extends Exception
{
    public function __construct(string $message = 'Address not found', int $code = 404, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
