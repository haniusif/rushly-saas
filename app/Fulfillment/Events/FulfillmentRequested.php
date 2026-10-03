<?php

namespace App\Fulfillment\Events;

use App\Fulfillment\Models\Fulfillment;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired the moment FulfillmentService creates the Fulfillment row and
 * before it calls the strategy's execute(). Listeners can gate / audit /
 * enrich the fulfillment before it actually runs.
 */
class FulfillmentRequested
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly Fulfillment $fulfillment) {}
}
