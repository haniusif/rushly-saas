<?php

return [
    'label'     => 'Settings',
    'overview'  => 'Configure how your shipments are priced and where they are picked up from: COD charges, delivery charges and pickup points.',
    'sub_pages' => [

        'cod-charges' => [
            'icon'    => 'Settings',
            'label'   => 'COD charges',
            'purpose' => 'Per-location COD percentage / flat fee applied when a parcel collects cash on delivery. Read-only on this side — managed by the operator.',
            'pages' => [
                ['path' => 'Index', 'desc' => 'Table of locations with the corresponding COD charge. Filter by location to find a specific row.'],
            ],
            'fields' => [
                'location', 'charge',
            ],
            'cross_links' => 'Drives the COD fee calculation when you Create a shipment with a non-zero cash_collection.',
            'notes'       => 'To request a change, open a support ticket. The operator can adjust the rates and they take effect on the next shipment you create.',
        ],

        'delivery-charges' => [
            'icon'    => 'Settings',
            'label'   => 'Delivery charges',
            'purpose' => 'Your pricing matrix: per delivery category and weight band, the rate for Same-day, Next-day, Main-city and Remote-area.',
            'pages' => [
                ['path' => 'Index', 'desc' => 'Rows are categories × weight bands. Columns are the four delivery types. Active rows are highlighted.'],
            ],
            'fields' => [
                'category', 'weight', 'same_day', 'next_day', 'sub_city', 'outside_city', 'status',
            ],
            'cross_links' => 'Used by Create shipment to compute the delivery charge from the chosen category + weight + delivery type + city.',
            'notes'       => 'If a needed (category, weight) row does not exist, the form falls back to the closest higher weight band. Talk to Support to add new bands.',
        ],

        'pickup-points' => [
            'icon'    => 'Store',
            'label'   => 'Pickup points (shops)',
            'purpose' => 'The list of your physical pickup locations — each parcel is collected from one of them. Add a new one when you open a new branch.',
            'pages' => [
                ['path' => 'Index',  'desc' => 'List of registered pickup points: name, contact, address, status.'],
                ['path' => 'Create', 'desc' => 'Add a new pickup point: name, contact person, phone, city, area, address.'],
                ['path' => 'Edit',   'desc' => 'Update an existing pickup point. Activate / deactivate from the actions menu.'],
            ],
            'fields' => [
                'name', 'contact', 'phone', 'city', 'area', 'address', 'status',
            ],
            'status_flow' => [
                ['label' => 'Active',   'tone' => 'ok'],
                ['label' => 'Inactive', 'tone' => 'default'],
            ],
            'cross_links' => 'Selected on every shipment in Create — only Active pickup points are listed in the dropdown.',
            'notes'       => 'Deactivating a pickup point does not affect parcels already picked up from it; it only hides it from new shipments.',
        ],

    ],
];
