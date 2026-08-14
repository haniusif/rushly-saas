<?php

namespace App\Shipping\Exceptions;

/**
 * Thrown when the provider is unreachable, returned 5xx, or doesn't support
 * the requested operation. Retryable — job runners should attempt again with
 * backoff.
 */
class ProviderUnavailableException extends ShippingException
{
}
