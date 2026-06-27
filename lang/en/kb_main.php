<?php

return [
    'label'    => 'Main Dashboard',
    'overview' => 'Landing dashboard for the admin panel: KPI cards, revenue charts, shipment status breakdown, and quick links into the rest of the app.',
    'sub_pages' => [

        'dashboard' => [
            'icon'    => 'LayoutDashboard',
            'label'   => 'Dashboard',
            'purpose' => 'Real-time KPIs, ledger summaries, and parcel-pipeline status across the entire logistics platform. High-level overview of business metrics, revenue flows, and recent shipment activity with date filtering.',
            'pages' => [
                ['path' => 'Index', 'desc' => 'Six KPI cards (Parcels / Users / Merchants / Couriers / Hubs / Accounts), parcel-pipeline progress bar (assigned / partial / delivered), ledger summary (income / expense / net for Courier, Couriers, Merchants, VAT, Bank, Hubs), recent-parcels table, three 7-day sparkline charts (Income vs Expense, Merchant Revenue, Courier Revenue), and a top-hubs bar chart.'],
            ],
            'fields' => [
                'parcels_count', 'users_count', 'merchants_count', 'deliverymen_count', 'hubs_count', 'accounts_count',
                'pipeline_assigned', 'pipeline_partial_delivered', 'pipeline_delivered',
                'courier_income', 'courier_expense', 'deliveryman_income', 'deliveryman_expense',
                'merchant_income', 'merchant_expense', 'vat_income', 'vat_expense',
                'bank_income', 'bank_expense', 'hub_income', 'hub_expense',
                'recent_parcel_tracking_id', 'recent_parcel_merchant_name', 'recent_parcel_status',
                'recent_parcel_cash_collection', 'recent_parcel_created_at', 'hub_parcels_count',
            ],
            'cross_links' => 'Parcels (pipeline card + recent-parcels table), Merchants, Users, Couriers, Hubs, Accounts.',
            'notes'       => 'Date range filterable via filter_date (defaults to last 7 days). Role-based projection — Super Admin sees subscription/company metrics, merchants see only their own data, company admins see the consolidated company ledger. Sparkline charts show income + expense with dual-axis. Recent-parcels table is paginated to 5. Status codes mapped in StatusPill: 1=Pending, 2=Picked, 3=In transit, 4=At hub, 5=Assigned, 6=Out for delivery, 9=Delivered, 10=Partial. Hub bar chart limited to top 4 hubs by parcel count.',
        ],

    ],
];
