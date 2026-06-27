<?php

return [
    'label'    => 'Billing',
    'overview' => 'SaaS billing for tenants: plan subscriptions, subscription history, and consolidated reports.',
    'sub_pages' => [

        'subscribe' => [
            'icon'    => 'Bell',
            'label'   => 'Subscribe',
            'purpose' => 'Newsletter / opt-in tracker — shows email addresses that have signed up to the company newsletter, with the timestamp of each signup. Not related to SaaS plan subscriptions.',
            'pages' => [
                ['path' => 'Index', 'desc' => 'Paginated list of newsletter subscribers — email + subscription date. 15 items per page.'],
            ],
            'fields' => ['email', 'created_at'],
            'cross_links' => 'Used alongside the Dashboard and the Admin section. Sign-ups originate from public marketing-site forms (CMS / Front Web).',
            'notes'       => 'Company-scoped. Route bound to SalaryGenerateController@subscribe (historical naming). Distinct from the plan-subscription module below.',
        ],

        'subscription' => [
            'icon'    => 'Receipt',
            'label'   => 'Subscription',
            'purpose' => 'Catalogue of available SaaS plans with pricing, features, and purchase options. Shows current subscription status and lets the user upgrade or downgrade via Stripe.',
            'pages' => [
                ['path' => 'Index (Plans)', 'desc' => 'Three-column grid of all active plans (name, price, description, parcel count, modules, Subscribe button). Shows an "Active" badge with remaining days for the current plan, or "Expired" if lapsed. Module list is expandable.'],
                ['path' => 'History',       'desc' => 'Paginated table (10 per page) of all subscription records across the company — company name, user details, plan, price, parcel count, deliveryman count, days count, start and expiry dates. Super-admin can filter by company.'],
            ],
            'fields' => [
                'plan_id', 'plan_name', 'plan_price', 'plan_interval', 'modules',
                'parcel_count', 'deliveryman_count', 'days_count',
                'start_date', 'expired_date',
            ],
            'status_flow' => [
                ['label' => 'Active',  'tone' => 'ok'],
                ['label' => 'Expired', 'tone' => 'bad'],
            ],
            'cross_links' => 'Stripe gateway for payment; Plan definition (modules, parcel cap); Company / merchant scoping.',
            'notes'       => 'Plan tier derived from parcel-count + module access (Plan.modules array). Stripe is gated by the global stripe_status setting. Currency reflects general settings. Routes: subscription.index, admin.subscription.history (PlanController@subscription / @subscriptionHistory).',
        ],

        'reports' => [
            'icon'    => 'ScrollText',
            'label'   => 'Reports',
            'purpose' => 'Operational status reports for parcels with filtering by date range, merchant, hub and delivery status. Supports export and print for operational audits.',
            'pages' => [
                ['path' => 'Index',  'desc' => 'Filter form: date range, merchant selector (async), hub dropdown, multi-select parcel status. Results table groups parcels by status when filters are applied.'],
                ['path' => 'Print',  'desc' => 'Print-optimised view of the filtered set, grouped by status, with export and print buttons.'],
            ],
            'fields' => ['date_from', 'date_to', 'merchant_id', 'hub_id', 'parcel_status', 'parcel_id', 'tracking_id'],
            'cross_links' => 'Dashboard, plus the Merchant / Hub / Deliveryman report variants. Pulls from the Parcels module.',
            'notes'       => 'Blade template (not Inertia). Excel export available. Print page reached via a route parameter array. Requires parcel_status_reports permission. Routes: parcel.reports / parcel.filter.reports / parcel.reports.print.page (ReportsController).',
        ],

    ],
];
