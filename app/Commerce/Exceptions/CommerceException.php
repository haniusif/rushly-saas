<?php

namespace App\Commerce\Exceptions;

use Exception;

/**
 * Base class for all Commerce module exceptions. Carries an optional
 * payload (provider response, HTTP status) for diagnostic surfaces (logs,
 * admin UI). Mirrors App\Shipping\Exceptions\ShippingException intentionally.
 */
class CommerceException extends Exception
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
