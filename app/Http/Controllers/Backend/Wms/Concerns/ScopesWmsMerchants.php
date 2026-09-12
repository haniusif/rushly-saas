<?php

namespace App\Http\Controllers\Backend\Wms\Concerns;

use App\Models\Backend\Merchant;
use Closure;

/**
 * Only merchants subscribed to the fulfillment or storage service
 * (Merchant::SERVICE_KEYS) can hold WMS stock. Shared by the WMS forms
 * that pick a merchant (products, receiving) so the list and the
 * server-side check never drift apart.
 */
trait ScopesWmsMerchants
{
    /**
     * @param int|null $keepId  keep this merchant in the list even without the
     *                          service, so an edit form never opens on a blank select
     */
    protected function wmsMerchants(?int $keepId = null)
    {
        return Merchant::companywise()
            ->where(function ($q) use ($keepId) {
                $q->whereJsonContains('services', 'fulfillment')
                  ->orWhereJsonContains('services', 'storage');
                if ($keepId) $q->orWhere('id', $keepId);
            })
            ->orderBy('business_name')
            ->get(['id', 'business_name']);
    }

    /** Validation closure for a merchant_id field. */
    protected function merchantHasWmsService(): Closure
    {
        return function ($attribute, $value, $fail) {
            $m = Merchant::companywise()->find($value);
            if (!$m || !($m->hasService('fulfillment') || $m->hasService('storage'))) {
                $fail(__('This merchant does not have the fulfillment or storage service.'));
            }
        };
    }
}
