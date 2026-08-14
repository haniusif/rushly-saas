<?php

namespace App\Commerce\Exceptions;

/**
 * Thrown when the storefront is unreachable, returned 5xx, or doesn't
 * support the requested operation. Retryable — job runners should attempt
 * again with backoff.
 */
class ProviderUnavailableException extends CommerceException
{
}
