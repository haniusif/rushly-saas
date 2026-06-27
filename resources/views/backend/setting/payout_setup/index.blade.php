@extends('backend.partials.master')
@section('title')
    {{ __('menus.pay_out') }} {{ __('menus.settings') }}
@endsection
@section('maincontent')
    <div class="container-fluid dashboard-content">
        <div class="tw-px-1 tw-pt-4 sm:tw-px-2">

            <nav class="tw-flex tw-items-center tw-gap-2 tw-text-xs tw-text-gray-500 tw-mb-4">
                <a href="{{ route('dashboard.index') }}" class="hover:tw-text-brand-600 tw-no-underline">{{ __('menus.dashboard') }}</a>
                <i class="fa fa-angle-right tw-text-[10px] tw-text-gray-400 tw-rtl-flip"></i>
                <span class="tw-text-gray-700">{{ __('menus.settings') }}</span>
                <i class="fa fa-angle-right tw-text-[10px] tw-text-gray-400 tw-rtl-flip"></i>
                <span class="tw-text-gray-700 tw-font-medium">{{ __('menus.pay_out') }}</span>
            </nav>

            <div class="tw-mb-4">
                <h1 class="tw-text-2xl tw-font-semibold tw-text-gray-900 tw-mb-1">{{ __('menus.pay_out') }} {{ __('menus.settings') }}</h1>
                <p class="tw-text-sm tw-text-gray-500 tw-m-0">Configure payment gateways for merchant payouts and customer payments.</p>
            </div>

            @php
                $canEdit  = hasPermission('payout_setup_settings_update');
                $gateways = [
                    'paypal' => [
                        'name'    => 'PayPal',
                        'route'   => \App\Enums\PayoutSetup::PAYPAL,
                        'icon'    => 'fa fa-paypal',
                        'tint'    => 'tw-bg-blue-50 tw-text-blue-600',
                        'fields'  => [
                            ['key' => 'paypal_client_id',     'label' => __('levels.paypal_client_id')],
                            ['key' => 'paypal_client_secret', 'label' => __('levels.paypal_client_secret')],
                            ['key' => 'paypal_mode',          'label' => __('levels.test_mode')],
                        ],
                        'switches' => [['key' => 'paypal_status', 'label' => __('levels.status')]],
                    ],
                    'stripe' => [
                        'name'    => 'Stripe',
                        'route'   => \App\Enums\PayoutSetup::STRIPE,
                        'icon'    => 'fa fa-credit-card',
                        'tint'    => 'tw-bg-violet-50 tw-text-violet-600',
                        'fields'  => [
                            ['key' => 'stripe_publishable_key', 'label' => __('levels.stripe_publishable_key')],
                            ['key' => 'stripe_secret_key',      'label' => __('levels.stripe_secret_key')],
                        ],
                        'switches' => [['key' => 'stripe_status', 'label' => __('levels.status')]],
                    ],
                    'razorpay' => [
                        'name'    => 'Razorpay',
                        'route'   => \App\Enums\PayoutSetup::RAZORPAY,
                        'icon'    => 'fa fa-credit-card-alt',
                        'tint'    => 'tw-bg-sky-50 tw-text-sky-600',
                        'fields'  => [
                            ['key' => 'razorpay_key',    'label' => __('levels.razorpay_key')],
                            ['key' => 'razorpay_secret', 'label' => __('levels.razorpay_secret')],
                        ],
                        'switches' => [['key' => 'razorpay_status', 'label' => __('levels.status')]],
                    ],
                    'skrill' => [
                        'name'    => 'Skrill',
                        'route'   => \App\Enums\PayoutSetup::SKRILL,
                        'icon'    => 'fa fa-money',
                        'tint'    => 'tw-bg-purple-50 tw-text-purple-600',
                        'fields'  => [
                            ['key' => 'skrill_merchant_email', 'label' => __('levels.skrill_merchant_email')],
                        ],
                        'switches' => [['key' => 'skrill_status', 'label' => __('levels.status')]],
                    ],
                    'sslcommerz' => [
                        'name'    => 'SSL Commerz',
                        'route'   => \App\Enums\PayoutSetup::SSL_COMMERZ,
                        'icon'    => 'fa fa-shield',
                        'tint'    => 'tw-bg-emerald-50 tw-text-emerald-600',
                        'fields'  => [
                            ['key' => 'sslcommerz_store_id',       'label' => __('levels.sslcommerz_store_id')],
                            ['key' => 'sslcommerz_store_password', 'label' => __('levels.sslcommerz_store_password')],
                        ],
                        'switches' => [
                            ['key' => 'sslcommerz_testmode', 'label' => __('levels.test_mode')],
                            ['key' => 'sslcommerz_status',   'label' => __('levels.status')],
                        ],
                    ],
                    'aamarpay' => [
                        'name'    => 'Aamarpay',
                        'route'   => \App\Enums\PayoutSetup::AAMARPAY,
                        'icon'    => 'fa fa-money',
                        'tint'    => 'tw-bg-amber-50 tw-text-amber-600',
                        'fields'  => [
                            ['key' => 'aamarpay_store_id',      'label' => __('levels.aamarpay_store_id')],
                            ['key' => 'aamarpay_signature_key', 'label' => __('levels.aamarpay_signature_key')],
                        ],
                        'switches' => [
                            ['key' => 'aamarpay_sendbox_mode', 'label' => __('levels.sendbox_mode')],
                            ['key' => 'aamarpay_status',       'label' => __('levels.status')],
                        ],
                    ],
                    'bkash' => [
                        'name'    => 'Bkash',
                        'route'   => \App\Enums\PayoutSetup::BKASH,
                        'icon'    => 'fa fa-mobile-phone',
                        'tint'    => 'tw-bg-rose-50 tw-text-rose-600',
                        'fields'  => [
                            ['key' => 'bkash_app_id',     'label' => __('levels.bkash_app_id')],
                            ['key' => 'bkash_app_secret', 'label' => __('levels.bkash_app_secret')],
                            ['key' => 'bkash_username',   'label' => __('levels.bkash_username')],
                            ['key' => 'bkash_password',   'label' => __('levels.bkash_password'), 'type' => 'password'],
                        ],
                        'switches' => [
                            ['key' => 'bkash_test_mode', 'label' => __('levels.bkash_test_mode')],
                            ['key' => 'bkash_status',    'label' => __('levels.status')],
                        ],
                    ],
                ];
            @endphp

            <div class="tw-grid tw-grid-cols-1 lg:tw-grid-cols-2 tw-gap-4">
                @foreach ($gateways as $g)
                    @php $statusOn = (int) old($g['switches'][count($g['switches'])-1]['key'], globalSettings($g['switches'][count($g['switches'])-1]['key'])) == \App\Enums\Status::ACTIVE; @endphp
                    <div class="tw-bg-white tw-border tw-border-gray-100 tw-rounded-xl tw-shadow-card tw-overflow-hidden">
                        <div class="tw-flex tw-items-center tw-justify-between tw-gap-3 tw-px-6 tw-py-4 tw-border-b tw-border-gray-100">
                            <div class="tw-flex tw-items-center tw-gap-3">
                                <span class="tw-shrink-0 tw-w-10 tw-h-10 tw-rounded-lg tw-flex tw-items-center tw-justify-center {{ $g['tint'] }}">
                                    <i class="{{ $g['icon'] }} tw-text-lg"></i>
                                </span>
                                <div>
                                    <h2 class="tw-text-base tw-font-semibold tw-text-gray-900 tw-m-0">{{ $g['name'] }}</h2>
                                    <p class="tw-text-xs tw-text-gray-500 tw-mt-0.5 tw-m-0">{{ count($g['fields']) }} fields</p>
                                </div>
                            </div>
                            <span class="tw-inline-flex tw-items-center tw-gap-1.5 tw-px-2 tw-py-0.5 tw-text-[10px] tw-font-medium tw-rounded-full {{ $statusOn ? 'tw-bg-emerald-50 tw-text-emerald-700' : 'tw-bg-gray-100 tw-text-gray-500' }}">
                                <span class="tw-w-1.5 tw-h-1.5 tw-rounded-full {{ $statusOn ? 'tw-bg-emerald-500' : 'tw-bg-gray-400' }}"></span>
                                {{ $statusOn ? 'Active' : 'Inactive' }}
                            </span>
                        </div>

                        @if ($canEdit)
                            <form action="{{ route('payout.setup.settings.update', $g['route']) }}" method="POST" enctype="multipart/form-data">
                                @method('PUT')
                                @csrf
                        @endif
                                <div class="tw-p-6 tw-space-y-4">
                                    @foreach ($g['fields'] as $f)
                                        <div>
                                            <label for="{{ $f['key'] }}" class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-1.5">
                                                {{ $f['label'] }} <span class="tw-text-red-500">*</span>
                                            </label>
                                            <input id="{{ $f['key'] }}" type="{{ $f['type'] ?? 'text' }}" name="{{ $f['key'] }}"
                                                   placeholder="{{ $f['label'] }}" autocomplete="off"
                                                   class="tw-input tw-w-full tw-h-10 tw-px-3 tw-text-sm tw-bg-white tw-border tw-rounded-lg @error($f['key']) tw-border-red-300 @else tw-border-gray-200 @enderror"
                                                   value="{{ old($f['key'], globalSettings($f['key'])) }}" {{ $canEdit ? '' : 'readonly' }}>
                                            @error($f['key'])<small class="tw-block tw-text-xs tw-text-red-500 tw-mt-1">{{ $message }}</small>@enderror
                                        </div>
                                    @endforeach

                                    <div class="tw-flex tw-flex-wrap tw-gap-x-6 tw-gap-y-3 tw-pt-2 tw-border-t tw-border-gray-100">
                                        @foreach ($g['switches'] as $s)
                                            <label class="tw-inline-flex tw-items-center tw-gap-2 tw-cursor-pointer">
                                                <span class="tw-relative tw-inline-block tw-w-9 tw-h-5">
                                                    <input type="checkbox" class="tw-peer tw-absolute tw-opacity-0 tw-w-0 tw-h-0" name="{{ $s['key'] }}"
                                                           @if (old($s['key'], globalSettings($s['key'])) == \App\Enums\Status::ACTIVE) checked @endif {{ $canEdit ? '' : 'disabled' }}>
                                                    <span class="tw-absolute tw-inset-0 tw-bg-gray-300 peer-checked:tw-bg-brand-600 tw-rounded-full tw-transition-colors"></span>
                                                    <span class="tw-absolute tw-top-0.5 tw-left-0.5 tw-w-4 tw-h-4 tw-bg-white tw-rounded-full tw-shadow peer-checked:tw-translate-x-4 tw-transition-transform"></span>
                                                </span>
                                                <span class="tw-text-sm tw-text-gray-700">{{ $s['label'] }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                        @if ($canEdit)
                                <div class="tw-flex tw-items-center tw-justify-end tw-px-6 tw-py-4 tw-border-t tw-border-gray-100 tw-bg-gray-50">
                                    <button type="submit" class="tw-inline-flex tw-items-center tw-h-10 tw-px-5 tw-text-sm tw-font-medium tw-text-white tw-bg-brand-600 hover:tw-bg-brand-700 tw-rounded-lg tw-border-0">
                                        {{ __('levels.save_change') }}
                                    </button>
                                </div>
                            </form>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endsection()
