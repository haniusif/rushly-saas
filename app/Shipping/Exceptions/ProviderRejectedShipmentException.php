<?php

namespace App\Shipping\Exceptions;

/**
 * Thrown when the provider validated the request and refused it (4xx-ish).
 * Indicates a data problem, not a transport problem — retrying with the same
 * payload won't help.
 */
class ProviderRejectedShipmentException extends ShippingException
{
}
