<?php

namespace App\Shipping\Exceptions;

use Exception;

/**
 * Base class for all shipping module exceptions. Carries an optional payload
 * (provider response, HTTP status) for diagnostic surfaces (logs, admin UI).
 */
class ShippingException extends Exception
{
    public function __construct(
        string $message,
        public readonly array $payload = [],
        ?\Throwable $previous = null,
        int $code = 0,
    ) {
        parent::__construct($message, $code, $previous);
    }
}
