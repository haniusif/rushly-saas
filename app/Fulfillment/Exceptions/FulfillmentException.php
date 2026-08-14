<?php

namespace App\Fulfillment\Exceptions;

use Exception;

class FulfillmentException extends Exception
{
    public function __construct(
        string $message,
        public readonly array $payload = [],
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
