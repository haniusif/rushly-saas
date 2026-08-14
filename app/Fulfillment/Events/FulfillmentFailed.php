<?php

namespace App\Fulfillment\Events;

use App\Fulfillment\Models\Fulfillment;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class FulfillmentFailed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Fulfillment $fulfillment,
        public readonly string $reason,
    ) {}
}
