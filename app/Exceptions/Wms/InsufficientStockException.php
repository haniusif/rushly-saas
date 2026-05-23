<?php

namespace App\Exceptions\Wms;

use RuntimeException;

class InsufficientStockException extends RuntimeException
{
    public function __construct(
        public int $productId,
        public int $locationId,
        public int $requested,
        public int $available,
        string $message = ''
    ) {
        if (!$message) {
            $message = "Insufficient stock for product #{$productId} at location #{$locationId}: requested {$requested}, available {$available}.";
        }
        parent::__construct($message);
    }
}
