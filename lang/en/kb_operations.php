<?php

return [
    'label'    => 'Operations',
    'overview' => 'Day-to-day field execution: courier roster, transport routes (TMS), warehouse branches, merchant accounts, and pickup requests.',
    'sub_pages' => [

        'couriers' => [
            'icon'    => 'Truck',
            'label'   => 'Couriers',
            'purpose' => 'Manages the courier / delivery-personnel roster with multi-tier employment types (freelancer, outsourced, company-employed). Tracks performance metrics (charges, balances), compliance documents, and operational assignments to hubs and areas.',
            'pages' => [
                ['path' => 'Index',  'desc' => 'List all couriers with filters by name/email/phone; shows status (Active/Suspended/On leave/Ended), charges, balances and hub assignment.'],
                ['path' => 'Create', 'desc' => 'Multi-step wizard (7 sections) covering personal info, identity docs, address, employment type, licensing, banking (freelancers) and photo uploads.'],
                ['path' => 'Edit',   'desc' => 'Same wizard interface; password field can be left blank to retain current credentials.'],
            ],
            'fields' => [
                'name', 'mobile', 'email', 'gender', 'dob', 'nationality',
                'id_type', 'id_number', 'id_expiry', 'driver_type', 'employee_number',
                'supplier_company_id', 'hub_id', 'direct_manager_id', 'operational_area_id',
                'salary', 'delivery_charge', 'pickup_charge', 'return_charge',
                'opening_balance', 'license_number', 'iban', 'status',
            ],
            'status_flow' => [
                ['label' => 'Active',         'tone' => 'ok'],
                ['label' => 'Suspended',      'tone' => 'warn'],
                ['label' => 'On leave',       'tone' => 'info'],
                ['label' => 'Contract ended', 'tone' => 'bad'],
            ],
            'cross_links' => 'TMS for real-time tracking; Hubs for assignment; Merchants for delivery assignment via the parcel system. Cash-received settlements and the salary module link in here too.',
            'notes'       => 'Subscription-limited by delivery_man_count. 30-day contract-expiry warnings. Direct-manager scope is company admins / incharges / hub users. Freelancers require IBAN, outsourced require a supplier company, company couriers require an employee number. All couriers support geolocation (lat/lng). Activity log tracks balance changes.',
        ],

        'tms' => [
            'icon'    => 'Map',
            'label'   => 'TMS',
            'purpose' => 'Transport management dashboard with real-time visibility into the courier network — status, shipment distribution by stage, geo-mapped personnel and runsheet exports.',
            'pages' => [
                ['path' => 'Index',                'desc' => 'Dashboard with date filter, shipment-status metrics (New / Ready / Picked / OFD / Not Delivered / Delivered), two courier lists (with/without shipments), online/offline counters, Google Map with markers, and runsheet export.'],
                ['path' => 'Print Runsheet (one)', 'desc' => 'Excel / HTML export of all shipments assigned to one courier on a chosen date; sanitised filename.'],
                ['path' => 'Print Runsheet (bulk)','desc' => 'Excel / HTML export aggregating multiple couriers; filters by date and driver-id list.'],
            ],
            'fields' => [
                'courier_name', 'mobile', 'shipment_count', 'pending_count', 'delivered_count',
                'status', 'lat', 'lng', 'hub_geo', 'parcel_status_breakdown', 'date_filter',
            ],
            'status_flow' => [
                ['label' => 'Online',  'tone' => 'ok'],
                ['label' => 'Offline', 'tone' => 'bad'],
            ],
            'cross_links' => 'Couriers (the roster being tracked), Hubs (hub locations on the map), Parcels (statuses feeding the metrics). Google Maps API powers the geo view.',
            'notes'       => 'Requires a Google Maps API key. Online/offline is determined by presence of delivery_lat/delivery_long (not null = online). Shipment assignment comes from ParcelEvent.delivery_man_id. "OFD" = Out For Delivery (ParcelStatus::DELIVERY_MAN_ASSIGN). HTML view is print-friendly.',
        ],

        'hubs' => [
            'icon'    => 'Building2',
            'label'   => 'Hubs',
            'purpose' => 'Centralised warehouse / branch directory with geo-coordinates for the logistics hub network. Tracks hub contact, location, operational status, and parcel flow through facilities.',
            'pages' => [
                ['path' => 'Index',  'desc' => 'Filterable list (name/phone). Columns: name, phone, address, status toggle. Actions: create, edit, view, delete.'],
                ['path' => 'Create', 'desc' => 'Name, phone, address (Google Places autocomplete), map picker for lat/lng, status (Active/Inactive).'],
                ['path' => 'Edit',   'desc' => 'Same form, pre-filled; map updates as coordinates change.'],
                ['path' => 'View',   'desc' => 'Hub detail with parcel breakdown by status; cash-collection sums for delivered / partial-delivered / in-transit; paginated parcel list.'],
            ],
            'fields' => ['name', 'phone', 'address', 'hub_lat', 'hub_long', 'status', 'current_balance', 'company_id'],
            'status_flow' => [
                ['label' => 'Active',   'tone' => 'ok'],
                ['label' => 'Inactive', 'tone' => 'bad'],
            ],
            'cross_links' => 'Couriers (assigned hub_id), Merchants (delivery-charge tiers per hub), Hub Incharges (management personnel), Hub Payments (settlement). Parcels are filterable by pickup origin hub.',
            'notes'       => 'Quick-store endpoint allows inline hub creation with a JSON response. Multi-tenant scoped by company_id. The view page aggregates cash_collection by status. Geolocation is required for the map display.',
        ],

        'merchants' => [
            'icon'    => 'Users',
            'label'   => 'Merchants',
            'purpose' => 'Client/seller account management — business profiles, service coverage (country/city), payment methods, delivery-charge tiers, and financial balances. Lifecycle from application to active operations with KYC documents.',
            'pages' => [
                ['path' => 'Index',             'desc' => 'Card/list view toggle. Filters: business name, owner, email. Shows merchant profile, coverage, services (Last-mile / Fulfillment / Storage), wallet status, balances, and action menu (view, edit, invoice, impersonate, copy apply link).'],
                ['path' => 'Create',            'desc' => 'Multi-section form (Account, Business, COD charges, Documents, Extras): owner/business details, KYC files (NID, trade licence), opening balance, VAT, payment period, return charge %, service flags, reference contact.'],
                ['path' => 'Edit',              'desc' => 'Pre-filled merchant profile; update business info, delivery charges per geography, KYC docs, payment methods, and service activation.'],
                ['path' => 'View',              'desc' => 'Dashboard with merchant stats, linked shops, payments, delivery charges and transaction history.'],
                ['path' => 'Invoice Generate',  'desc' => 'Creates a settlement invoice for merchant financial records.'],
                ['path' => 'Impersonate',       'desc' => 'Admin "login-as" for the merchant account (support/debugging). Gated by merchant_update.'],
            ],
            'fields' => [
                'business_name', 'name', 'mobile', 'email', 'address', 'status',
                'wallet_use_activation', 'current_balance', 'opening_balance', 'vat',
                'payment_period', 'return_charges', 'cod_charges', 'services',
                'countries', 'cities', 'covers_all_cities', 'nid_id', 'trade_license',
                'merchant_unique_id',
            ],
            'status_flow' => [
                ['label' => 'Active',        'tone' => 'ok'],
                ['label' => 'Inactive',      'tone' => 'bad'],
                ['label' => 'Wallet active', 'tone' => 'info'],
                ['label' => 'Wallet off',    'tone' => 'warn'],
            ],
            'cross_links' => 'Delivery Charges (per-merchant rate config), Shops (merchant pickup points), Payments (settlement), Parcels (created by the merchant). Public KYC apply form at /merchant/apply requires no auth.',
            'notes'       => 'Multi-tenant scoped by company_id. wallet_use_activation toggles prepaid mode. services = 3 tiers (Last-mile / Fulfillment / Storage). cod_charges stored as JSON keyed by geography (inside_city / sub_city / outside_city). computed_balance is derived from parcel settlements; wallet_balance is tracked separately for prepaid merchants. Impersonate sets session.impersonator_id.',
        ],

        'pickup-request' => [
            'icon'    => 'Inbox',
            'label'   => 'Pickup Request',
            'purpose' => 'Admin view of merchant-initiated pickup requests for outbound shipment collection. Split into regular (bulk) and express (individual parcels) request types.',
            'pages' => [
                ['path' => 'Regular', 'desc' => 'Bulk pickup requests from merchants: merchant info (name/email/phone/avatar), pickup address, estimated parcel quantity, notes.'],
                ['path' => 'Express', 'desc' => 'Individual express shipments: merchant info, recipient name/phone, COD amount, invoice number, weight, exchange flag, notes.'],
            ],
            'fields' => [
                'merchant_name', 'merchant_email', 'merchant_phone', 'address',
                'parcel_quantity', 'name', 'phone', 'cod_amount', 'invoice',
                'weight', 'exchange', 'note', 'request_type',
            ],
            'cross_links' => 'Merchants (request originator), Couriers (assigned for pickup execution). Creation flow lives in the merchant panel; admin sees the queue here.',
            'notes'       => 'Read-only on the admin side (no edit/delete — that lives in the merchant panel). request_type enum distinguishes regular (parcel_quantity) from express (individual parcel detail). exchange flag (0/1) indicates whether the merchant can swap in a replacement parcel.',
        ],

    ],
];
