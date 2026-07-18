<?php
namespace App\Repositories\PayoutSetup;

use App\Enums\PayoutSetup;
use App\Enums\Status;
use App\Models\Backend\Setting;
use App\Repositories\PayoutSetup\PayoutSetupInterface;

class PayoutSetupRepository implements PayoutSetupInterface
{
    /**
     * Update a single payment gateway's settings.
     *
     * Two subtle traps this method has to defuse:
     * - Legacy Bootstrap Blade sent unchecked HTML checkboxes as the literal
     *   string 'on' when checked; the Inertia form now sends 1/0 as ints.
     *   Anything short of accepting both would silently write INACTIVE to
     *   every toggle whenever the client changes.
     * - Setting has $fillable = ['key','value'] so company_id can't be
     *   mass-assigned. Setting::create(['company_id' => ...]) silently drops
     *   the column and orphans the row (company_id null → invisible to the
     *   companywise scope). Use direct property assignment instead.
     */
    public function update($payment_method, $request)
    {
        try {
            // Per-gateway list of checkbox-like fields that need string→int
            // coercion. Keeping this as a map lets the loop stay generic.
            $statusKeys = match ((int) $payment_method) {
                PayoutSetup::STRIPE      => ['stripe_status'],
                PayoutSetup::SSL_COMMERZ => ['sslcommerz_testmode', 'sslcommerz_status'],
                PayoutSetup::PAYPAL      => ['paypal_status'],
                PayoutSetup::SKRILL      => ['skrill_status'],
                PayoutSetup::BKASH       => ['bkash_test_mode', 'bkash_status'],
                PayoutSetup::AAMARPAY    => ['aamarpay_sendbox_mode', 'aamarpay_status'],
                PayoutSetup::RAZORPAY    => ['razorpay_status'],
                default                  => [],
            };

            foreach ($statusKeys as $k) {
                $raw = $request->input($k);
                $isActive = $raw === 'on'
                    || $raw === true
                    || $raw === 1
                    || $raw === '1';
                $request[$k] = $isActive ? Status::ACTIVE : Status::INACTIVE;
            }

            $companyId   = settings()->id;
            $requestData = $request->except(['_method', '_token']);

            foreach ($requestData as $key => $value) {
                $setting = Setting::where('company_id', $companyId)->where('key', $key)->first()
                    ?? new Setting(['key' => $key]);
                $setting->company_id = $companyId;
                $setting->key        = $key;
                $setting->value      = (string) ($value ?? '');
                $setting->save();
            }
            return true;
        } catch (\Throwable $th) {
            \Log::error('PayoutSetupRepository::update failed', [
                'payment_method' => $payment_method,
                'error'          => $th->getMessage(),
            ]);
            return false;
        }
    }
}
