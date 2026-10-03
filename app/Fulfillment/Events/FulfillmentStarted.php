<?php

namespace App\Fulfillment\Events;

use App\Fulfillment\Models\Fulfillment;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired when a strategy successfully transitions a Fulfillment to
 * in_progress (started_at stamped, no error). Distinct from
 * FulfillmentCompleted (which fires on terminal success).
 */
class FulfillmentStarted
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly Fulfillment $fulfillment) {}
}
