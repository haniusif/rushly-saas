<?php

return [
    'label'     => 'Dashboard',
    'overview'  => 'Landing page for your merchant account — KPI cards, recent shipments, balance summary and quick links to the rest of the panel.',
    'sub_pages' => [

        'overview' => [
            'icon'    => 'LayoutDashboard',
            'label'   => 'Dashboard overview',
            'purpose' => 'Single screen with shipment KPIs, revenue and balance summaries, the last-eight-days trend, and a status breakdown. Everything respects the date filter at the top.',
            'pages' => [
                ['path' => 'Header + filter',  'desc' => 'Date filter (single day or range) plus your active-services badges (Last mile, Fulfillment, Storage).'],
                ['path' => 'KPI tiles',        'desc' => 'Four counters: total shipments, delivered, returned, in-transit. Each tile is a link into the matching filtered shipments list.'],
                ['path' => 'Amount cards',     'desc' => 'Three grouped cards — Active shipments amount (cash collection, selling price, net profit), Liquid / packaging / VAT, and Delivery / COD totals.'],
                ['path' => 'Charts',           'desc' => 'Line chart of the last 8 days (total, delivered, partial, returned) and a donut breakdown by current status.'],
                ['path' => 'All reports grid', 'desc' => '12 tiles of running totals: sales, delivery fees paid, VAT, net profit, current and opening balance, payment processing, paid amount, shops, parcel-bank items, payment requests.'],
            ],
            'fields' => [
                'parcel_kpis', 'active_amounts', 'fees_amounts', 'delivery_amounts',
                'series', 'pie', 'reports',
            ],
            'cross_links' => 'KPI tiles deep-link into Shipments. The amount cards mirror the same figures shown on the Total Summary report. Balance tiles match what appears on Statements.',
            'notes'       => 'Numbers update live as couriers confirm deliveries. When the date filter is active, every block on the page respects it — including the chart and reports grid.',
        ],

    ],
];
