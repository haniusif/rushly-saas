<?php

namespace App\Fulfillment\Strategies;

use App\Fulfillment\Contracts\FulfillmentStrategyInterface;
use App\Fulfillment\Exceptions\StrategyRejectedException;
use App\Fulfillment\Models\Fulfillment;
use App\Oms\Models\Order;

/**
 * "The merchant handles fulfillment themselves — Rushly just records
 * the order and stays out of it." Marks the Fulfillment complete
 * immediately with no external side effects.
 *
 * Useful as a fallback route for merchants who don't use Rushly's
 * fulfillment stack (they might use us purely for order collection or
 * COD tracking), and as the trivial happy-path for unit-testing the
 * routing pipeline without needing WMS or 3PL test doubles.
 */
class MerchantSelfStrategy implements FulfillmentStrategyInterface
{
    public function code(): string
    {
        return 'merchant_self';
    }

    public function execute(Fulfillment $fulfillment, Order $order): void
    {
        $fulfillment->status        = Fulfillment::STATUS_COMPLETED;
        $fulfillment->started_at    = now();
        $fulfillment->completed_at  = now();
        $fulfillment->last_error    = null;
        $fulfillment->payload       = array_merge((array) $fulfillment->payload, [
            'strategy_note' => 'Merchant handles fulfillment out-of-band; no Rushly-side action.',
        ]);
        $fulfillment->save();
    }

    public function cancel(Fulfillment $fulfillment): void
    {
        // Nothing to cancel externally. Flip status if not terminal.
        if ($fulfillment->isTerminal()) {
            throw new StrategyRejectedException(
                'Fulfillment is already terminal — cannot cancel.',
                ['status' => $fulfillment->status],
            );
        }
        $fulfillment->status = Fulfillment::STATUS_CANCELLED;
        $fulfillment->save();
    }
}
