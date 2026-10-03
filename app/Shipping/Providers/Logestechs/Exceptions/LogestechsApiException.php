<?php

namespace App\Shipping\Providers\Logestechs\Exceptions;

use App\Shipping\Exceptions\ShippingException;

/**
 * Provider-specific exception. Carries the HTTP status + decoded body for
 * diagnostic display in the admin UI. Use ProviderRejectedShipmentException /
 * ProviderUnavailableException at the boundary — this one is for finer-grained
 * provider-internal signaling.
 */
class LogestechsApiException extends ShippingException
{
}
