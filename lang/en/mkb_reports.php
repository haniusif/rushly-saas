<?php

return [
    'label'     => 'Reports',
    'overview'  => 'Operational reports: shipment status, total summary, account transactions and statements — filterable and exportable.',
    'sub_pages' => [

        'shipments-report' => [
            'icon'    => 'BarChart3',
            'label'   => 'Shipments report',
            'purpose' => 'Status-level rollup of your shipments for a chosen date range — count per status, total cash collection, ready to print or export.',
            'pages' => [
                ['path' => 'Filter',  'desc' => 'Pick a date range. Optional: filter by status, by city, by shop.'],
                ['path' => 'Results', 'desc' => 'Rows per status with count and aggregated cash collection. Totals shown in the footer.'],
                ['path' => 'Print',   'desc' => 'Open a print-friendly version of the report from the toolbar.'],
            ],
            'fields' => [
                'date_range', 'status', 'cash_collection', 'count',
            ],
            'cross_links' => 'Same shipments appear individually in Shipments. The aggregated cash-collection figure matches Total Summary.',
            'notes'       => 'The report is generated live each time you filter — there is no cache. Large date ranges can take a few seconds.',
        ],

        'total-summary' => [
            'icon'    => 'BarChart3',
            'label'   => 'Total summary',
            'purpose' => 'Single-page rollup of every money-related figure on your account for a chosen date range — sales, profit, accounts and payments.',
            'pages' => [
                ['path' => 'Filter',     'desc' => 'Pick a date range. The whole report respects it.'],
                ['path' => 'Sales',      'desc' => 'Total cash collection, selling price, liquid/fragile fees, packaging fees, VAT.'],
                ['path' => 'Profit',     'desc' => 'Delivery charge, COD charge, delivery charge VAT, net profit.'],
                ['path' => 'Accounts',   'desc' => 'Bank balance, bank opening balance.'],
                ['path' => 'Payments',   'desc' => 'Paid amount, pending amount, payable amount.'],
            ],
            'fields' => [
                'total_cash_collection', 'total_selling_price',
                'total_delivery_charge', 'net_profit_ammount',
                'bank_balance', 'paid_amount', 'pending_amount', 'payable_amount',
            ],
            'cross_links' => 'Numbers mirror those on the Dashboard amount cards and the Invoices list. The Payable / Paid figures match Payouts.',
            'notes'       => 'Apply a date filter to populate the report — by default no range is selected so all tiles read zero.',
        ],

        'account-transactions' => [
            'icon'    => 'Wallet',
            'label'   => 'Account transactions',
            'purpose' => 'Ledger of every credit and debit on your wallet / receivables account — payouts, refunds, payments received.',
            'pages' => [
                ['path' => 'Filter',  'desc' => 'Filter by date, transaction type or account.'],
                ['path' => 'Results', 'desc' => 'Rows per transaction: id, account, type, amount, status, request date.'],
            ],
            'fields' => [
                'transaction_id', 'account', 'type', 'amount', 'status', 'request_date',
            ],
            'cross_links' => 'Same individual entries that show up under Payments received and Payouts.',
            'notes'       => 'Read-only. To dispute an entry, open a support ticket with the transaction id.',
        ],

        'statements' => [
            'icon'    => 'FileText',
            'label'   => 'Statements',
            'purpose' => 'Per-parcel running ledger of every charge and credit the operator has posted to your account. The source of truth for your current balance.',
            'pages' => [
                ['path' => 'Filter',  'desc' => 'Filter by date, transaction type (INCOME / EXPENSE) or tracking id.'],
                ['path' => 'Results', 'desc' => 'Rows show date, tracking id, type, amount and the running balance after that line.'],
                ['path' => 'Details', 'desc' => 'Each row links to the shipment and the originating event (delivery, return, charge adjustment).'],
            ],
            'fields' => [
                'statement_id', 'date', 'tracking_id', 'type', 'amount', 'note',
            ],
            'cross_links' => 'Aggregated total at the bottom matches your Current balance on the Dashboard and the Payable amount on Total Summary.',
            'notes'       => 'Append-only — entries are never deleted. Corrections show up as a reversing line (with a negative amount), not as an edit.',
        ],

    ],
];
