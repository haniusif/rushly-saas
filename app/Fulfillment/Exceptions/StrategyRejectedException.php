<?php

namespace App\Fulfillment\Exceptions;

/**
 * Thrown when a strategy validates its inputs and refuses (missing
 * shipping connection, unknown warehouse, order too heavy, ...).
 * Non-retryable — retry with same inputs will fail the same way.
 */
class StrategyRejectedException extends FulfillmentException
{
}
