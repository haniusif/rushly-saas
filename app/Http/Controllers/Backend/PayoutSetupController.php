<?php

namespace App\Http\Controllers\Backend;

use App\Enums\PayoutSetup;
use App\Enums\Status;
use App\Http\Controllers\Controller;
use App\Models\Backend\MerchantOnlinePayment;
use App\Repositories\PayoutSetup\PayoutSetupInterface;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PayoutSetupController extends Controller
{
    protected $repo;
    protected $MOPmodel;

    public function __construct(PayoutSetupInterface $repo, MerchantOnlinePayment $MOPmodel)
    {
        $this->repo     = $repo;
        $this->MOPmodel = $MOPmodel;
    }

    public function index()
    {
        $gateways = $this->gatewayDefinitions();

        // Build each gateway's live payload: current field values +
        // switch states derived from Status::ACTIVE comparison.
        foreach ($gateways as &$g) {
            foreach ($g['fields'] as &$f) {
                $f['value'] = (string) (globalSettings($f['key']) ?? '');
            }
            unset($f);
            foreach ($g['switches'] as &$s) {
                $s['checked'] = (int) (globalSettings($s['key']) ?? 0) === Status::ACTIVE;
            }
            unset($s);
            // Header pill mirrors the *last* switch — that's always
            // `<gateway>_status` per the original Blade convention.
            $g['is_active'] = !empty($g['switches']) && end($g['switches'])['checked'];
        }
        unset($g);

        return Inertia::render('Admin/Payout/Setup', [
            'gateways'    => array_values($gateways),
            'permissions' => [
                'update' => hasPermission('payout_setup_settings_update'),
            ],
            't' => [
                'title'    => (__('menus.pay_out') ?: 'Payout') . ' ' . (__('menus.settings') ?: 'settings'),
                'subtitle' => 'Configure payment gateways for merchant payouts and customer payments.',
                'dashboard'=> __('menus.dashboard') ?: 'Dashboard',
                'settings' => __('menus.settings') ?: 'Settings',
                'pay_out'  => __('menus.pay_out') ?: 'Payout',
                'active'   => 'Active',
                'inactive' => 'Inactive',
                'fields'   => 'fields',
                'save'     => __('levels.save_change') ?: 'Save changes',
                'status'   => __('levels.status') ?: 'Status',
            ],
        ]);
    }

    public function PayoutSetupUpdate(Request $request, $paymentMethod)
    {
        if ($this->repo->update($paymentMethod, $request)) {
            Toastr::success(__('menus.payout_setup_updated'), __('message.success'));
            return redirect()->back();
        }
        Toastr::error(__('parcel.error_msg'), __('message.error'));
        return redirect()->back();
    }

    public function onlinePaymentList()
    {
        $payments = $this->MOPmodel::orderByDesc('id')->paginate(10);
        return view('backend.online_payment.online_payment_list', compact('payments'));
    }

    /**
     * The 7 supported gateways with their field + switch metadata.
     * Kept in one place so both the render and (potential) validation can
     * share the schema. Route ids come from the PayoutSetup enum which the
     * update endpoint dispatches on via {paymentmethod}.
     */
    private function gatewayDefinitions(): array
    {
        return [
            [
                'key'      => 'paypal',
                'name'     => 'PayPal',
                'icon'     => 'paypal',
                'tint'     => 'bg-blue-50 text-blue-600',
                'route'    => PayoutSetup::PAYPAL,
                'submit'   => route('payout.setup.settings.update', PayoutSetup::PAYPAL),
                'fields'   => [
                    ['key' => 'paypal_client_id',     'label' => __('levels.paypal_client_id')     ?: 'Client ID',     'type' => 'text'],
                    ['key' => 'paypal_client_secret', 'label' => __('levels.paypal_client_secret') ?: 'Client secret', 'type' => 'password'],
                    ['key' => 'paypal_mode',          'label' => __('levels.test_mode')            ?: 'Test mode',     'type' => 'text'],
                ],
                'switches' => [
                    ['key' => 'paypal_status', 'label' => __('levels.status') ?: 'Status'],
                ],
            ],
            [
                'key'      => 'stripe',
                'name'     => 'Stripe',
                'icon'     => 'stripe',
                'tint'     => 'bg-violet-50 text-violet-600',
                'route'    => PayoutSetup::STRIPE,
                'submit'   => route('payout.setup.settings.update', PayoutSetup::STRIPE),
                'fields'   => [
                    ['key' => 'stripe_publishable_key', 'label' => __('levels.stripe_publishable_key') ?: 'Publishable key', 'type' => 'text'],
                    ['key' => 'stripe_secret_key',      'label' => __('levels.stripe_secret_key')      ?: 'Secret key',      'type' => 'password'],
                ],
                'switches' => [
                    ['key' => 'stripe_status', 'label' => __('levels.status') ?: 'Status'],
                ],
            ],
            [
                'key'      => 'razorpay',
                'name'     => 'Razorpay',
                'icon'     => 'razorpay',
                'tint'     => 'bg-sky-50 text-sky-600',
                'route'    => PayoutSetup::RAZORPAY,
                'submit'   => route('payout.setup.settings.update', PayoutSetup::RAZORPAY),
                'fields'   => [
                    ['key' => 'razorpay_key',    'label' => __('levels.razorpay_key')    ?: 'Key ID',    'type' => 'text'],
                    ['key' => 'razorpay_secret', 'label' => __('levels.razorpay_secret') ?: 'Key secret','type' => 'password'],
                ],
                'switches' => [
                    ['key' => 'razorpay_status', 'label' => __('levels.status') ?: 'Status'],
                ],
            ],
            [
                'key'      => 'skrill',
                'name'     => 'Skrill',
                'icon'     => 'skrill',
                'tint'     => 'bg-purple-50 text-purple-600',
                'route'    => PayoutSetup::SKRILL,
                'submit'   => route('payout.setup.settings.update', PayoutSetup::SKRILL),
                'fields'   => [
                    ['key' => 'skrill_merchant_email', 'label' => __('levels.skrill_merchant_email') ?: 'Merchant email', 'type' => 'email'],
                ],
                'switches' => [
                    ['key' => 'skrill_status', 'label' => __('levels.status') ?: 'Status'],
                ],
            ],
            [
                'key'      => 'sslcommerz',
                'name'     => 'SSL Commerz',
                'icon'     => 'sslcommerz',
                'tint'     => 'bg-emerald-50 text-emerald-600',
                'route'    => PayoutSetup::SSL_COMMERZ,
                'submit'   => route('payout.setup.settings.update', PayoutSetup::SSL_COMMERZ),
                'fields'   => [
                    ['key' => 'sslcommerz_store_id',       'label' => __('levels.sslcommerz_store_id')       ?: 'Store ID',       'type' => 'text'],
                    ['key' => 'sslcommerz_store_password', 'label' => __('levels.sslcommerz_store_password') ?: 'Store password', 'type' => 'password'],
                ],
                'switches' => [
                    ['key' => 'sslcommerz_testmode', 'label' => __('levels.test_mode') ?: 'Test mode'],
                    ['key' => 'sslcommerz_status',   'label' => __('levels.status')    ?: 'Status'],
                ],
            ],
            [
                'key'      => 'aamarpay',
                'name'     => 'Aamarpay',
                'icon'     => 'aamarpay',
                'tint'     => 'bg-amber-50 text-amber-600',
                'route'    => PayoutSetup::AAMARPAY,
                'submit'   => route('payout.setup.settings.update', PayoutSetup::AAMARPAY),
                'fields'   => [
                    ['key' => 'aamarpay_store_id',      'label' => __('levels.aamarpay_store_id')      ?: 'Store ID',      'type' => 'text'],
                    ['key' => 'aamarpay_signature_key', 'label' => __('levels.aamarpay_signature_key') ?: 'Signature key', 'type' => 'password'],
                ],
                'switches' => [
                    ['key' => 'aamarpay_sendbox_mode', 'label' => __('levels.sendbox_mode') ?: 'Sandbox mode'],
                    ['key' => 'aamarpay_status',       'label' => __('levels.status')       ?: 'Status'],
                ],
            ],
            [
                'key'      => 'bkash',
                'name'     => 'bKash',
                'icon'     => 'bkash',
                'tint'     => 'bg-rose-50 text-rose-600',
                'route'    => PayoutSetup::BKASH,
                'submit'   => route('payout.setup.settings.update', PayoutSetup::BKASH),
                'fields'   => [
                    ['key' => 'bkash_app_id',     'label' => __('levels.bkash_app_id')     ?: 'App ID',     'type' => 'text'],
                    ['key' => 'bkash_app_secret', 'label' => __('levels.bkash_app_secret') ?: 'App secret', 'type' => 'password'],
                    ['key' => 'bkash_username',   'label' => __('levels.bkash_username')   ?: 'Username',   'type' => 'text'],
                    ['key' => 'bkash_password',   'label' => __('levels.bkash_password')   ?: 'Password',   'type' => 'password'],
                ],
                'switches' => [
                    ['key' => 'bkash_test_mode', 'label' => __('levels.bkash_test_mode') ?: 'Test mode'],
                    ['key' => 'bkash_status',    'label' => __('levels.status')          ?: 'Status'],
                ],
            ],
        ];
    }
}
