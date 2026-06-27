<?php

return [
    // Page chrome
    'title_create'     => 'New merchant',
    'title_edit'       => 'Edit merchant',
    'title_index'      => 'Merchants',

    // Section headers
    'account'          => 'Owner account',
    'business'         => 'Business',
    'cod_charges'      => 'COD charges (%)',
    'geography'        => 'Geography coverage',
    'extras'           => 'Extras',
    'documents'        => 'Documents',

    // Owner account fields
    'name'             => 'Owner name',
    'mobile'           => 'Mobile',
    'email'            => 'Email',
    'password'         => 'Password',
    'password_keep_hint' => 'Leave blank to keep the current password.',

    // Business fields
    'business_name'    => 'Business name',
    'address'          => 'Address',
    'hub'              => 'Hub',
    'status'           => 'Status',

    // COD areas
    'cod_inside_city'  => 'Inside city',
    'cod_sub_city'     => 'Sub city',
    'cod_outside_city' => 'Outside city',

    // Geography
    'countries'                 => 'Countries',
    'cities'                    => 'Cities',
    'covers_all_cities'         => 'Covers all cities in selected countries',
    'select_at_least_country'   => 'Select at least one country.',
    'select_cities_or_all'      => 'Select cities or check "covers all cities".',

    // Extras
    'opening_balance'  => 'Opening balance',
    'vat'              => 'VAT (%)',
    'payment_period'   => 'Payment period (days)',
    'return_charges'   => 'Return charges (%)',
    'wallet_use'       => 'Wallet use',
    'wallet_on'        => 'On',
    'wallet_off'       => 'Off',
    'reference_name'   => 'Reference name',
    'reference_phone'  => 'Reference phone',
    'services'         => 'Services',

    // Service labels (used to render lookups.services tags)
    'service_last_mile'   => 'Last mile',
    'service_fulfillment' => 'Fulfillment',
    'service_storage'     => 'Storage',

    // Documents / file uploads
    'avatar'                 => 'Avatar',
    'nid'                    => 'NID',
    'trade'                  => 'Trade license',
    'optional'               => 'optional',
    'choose_file'            => 'Choose file…',
    'replace_file'           => 'Replace…',
    'file_hint_types'        => 'JPEG / PNG / PDF',
    'file_hint_replace'      => 'Upload to replace existing',

    // Status labels (sent in lookups.statuses)
    'status_active'    => 'Active',
    'status_inactive'  => 'Inactive',

    // Footer caption + actions
    'footer_caption'   => 'Owner account, business profile, and documents are required. Brand theme + per-area delivery charges can be set after save from the merchant page.',
    'save'             => 'Save',
    'cancel'           => 'Cancel',
];
