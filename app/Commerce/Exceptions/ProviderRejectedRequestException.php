<?php

namespace App\Commerce\Exceptions;

/**
 * Thrown when the storefront validated the request and refused it (4xx-ish:
 * invalid order id, expired token after refresh failed, malformed payload).
 * Indicates a data problem, not a transport problem — retrying with the
 * same payload won't help.
 */
class ProviderRejectedRequestException extends CommerceException
{
}
