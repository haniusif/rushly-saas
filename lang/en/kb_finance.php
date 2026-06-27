<?php

return [
    'label'    => 'Finance',
    'overview' => 'Money flow: incoming payments, merchant payouts, COD accounts and the wallet-top-up request queue.',
    'sub_pages' => [

        'payment-received' => [
            'icon'    => 'BadgeDollarSign',
            'label'   => 'Payment Received',
            'purpose' => 'Read-only view of all merchant invoices across paid, processing and unpaid statuses. Operators track invoice settlement, cash collection against total charges, and current payable amounts to manage merchant billing.',
            'pages' => [
                ['path' => 'Index', 'desc' => 'Tabbed view (paid / processing / unpaid) with merchant name, invoice ID, date, cash collection, total charges and current payable balance. Links out to per-merchant invoice detail with PDF/CSV export.'],
            ],
            'fields' => [
                'merchant_name', 'merchant_email', 'invoice_id', 'invoice_date',
                'cash_collection', 'total_charge', 'current_payable', 'status',
            ],
            'status_flow' => [
                ['label' => 'Unpaid',     'tone' => 'default'],
                ['label' => 'Processing', 'tone' => 'info'],
                ['label' => 'Paid',       'tone' => 'ok'],
            ],
            'cross_links' => 'Accounts (settlement accounts), Payout (merchant settlement), Wallet Request (top-up flow).',
            'notes'       => 'Statuses: 0 = Unpaid, 2 = Processing, 3 = Paid. No create/edit/delete — read-only. Links into per-merchant invoice detail pages with PDF / CSV export.',
        ],

        'payout' => [
            'icon'    => 'CreditCard',
            'label'   => 'Payout',
            'purpose' => 'Historical record of all merchant online-payment settlements (Stripe, Razorpay, PayPal, Skrill, SSL Commerz, bKash, AmarPay). Operators verify transaction integrity and payment-method distribution.',
            'pages' => [
                ['path' => 'Index', 'desc' => 'List of recorded payouts: card type (payment method), merchant, source account, transaction ID, amount and timestamp.'],
            ],
            'fields' => [
                'card_type', 'merchant_name', 'merchant_email',
                'from_account', 'from_account_no', 'transaction_id', 'amount', 'created_at',
            ],
            'cross_links' => 'Accounts (source/destination), Wallet Request (merchant wallet recharges via PaymentType enum).',
            'notes'       => 'Read-only audit log. PaymentType ids: STRIPE (1), SSL_COMMERZ (2), PAYPAL (3), BKASH (5), SKRILL (7), AAMARPAY (8), RAZORPAY (9). Data sourced from MerchantOnlinePaymentReceived. No approval flow — rows are created post-transaction.',
        ],

        'accounts' => [
            'icon'    => 'DollarSign',
            'label'   => 'Accounts',
            'purpose' => 'Central financial-accounts ledger (COD, bank, mobile money, cash). Operators manage company operating accounts, track balances, and set opening balances for reconciliation.',
            'pages' => [
                ['path' => 'Index',  'desc' => 'Filterable list of accounts with holder name, bank/gateway, account number, current balance and opening balance.'],
                ['path' => 'Create', 'desc' => 'Add a new account. Gateway selection (Cash, Bank, bKash, Rocket, Nagad) conditionally shows the required fields for that gateway.'],
                ['path' => 'Edit',   'desc' => 'Update existing account details (legacy blade form).'],
                ['path' => 'View',   'desc' => 'Account details page (legacy blade).'],
            ],
            'fields' => [
                'gateway', 'bank', 'branch_name', 'account_holder_name', 'account_no',
                'balance', 'opening_balance', 'type', 'status',
            ],
            'status_flow' => [
                ['label' => 'Active',   'tone' => 'ok'],
                ['label' => 'Inactive', 'tone' => 'bad'],
            ],
            'cross_links' => 'Payout (destination accounts), Wallet Request (merchant withdrawal accounts).',
            'notes'       => 'Gateway-conditional fields: Cash = balance only; Bank = holder + account + bank + branch + opening; Mobile (3/4/5) = holder + mobile + type + opening. Status controls visibility in transaction forms.',
        ],

        'wallet-request' => [
            'icon'    => 'Wallet',
            'label'   => 'Wallet Request',
            'purpose' => 'Merchant wallet top-up request queue. Operators review pending recharge requests, approve to credit the merchant wallet, or reject with audit trail. Supports admin-initiated quick recharge.',
            'pages' => [
                ['path' => 'Index',           'desc' => 'Dual-tab view (All Transactions / Recharges Only) with status filters (Pending / Approved / Rejected) and summary cards for totals / counts.'],
                ['path' => 'Recharge Modal',  'desc' => 'Quick admin recharge via modal with preset amount buttons (500–20000).'],
            ],
            'fields' => [
                'merchant_name', 'merchant_phone', 'merchant_addr', 'user_image', 'created_at',
                'transaction_id', 'payment_method', 'amount', 'type', 'status',
            ],
            'status_flow' => [
                ['label' => 'Pending',  'tone' => 'warn'],
                ['label' => 'Approved', 'tone' => 'ok'],
                ['label' => 'Rejected', 'tone' => 'bad'],
            ],
            'cross_links' => 'Accounts (merchant settlement accounts), Payout (settlement methods).',
            'notes'       => 'Type enum: INCOME (1) = recharge request, EXPENSE (2) = deduction (action buttons only show for INCOME). WalletStatus: PENDING (1), APPROVED (2), REJECTED (3). WalletPaymentMethod: OFFLINE (1), WALLET (2). Approve/reject/delete each require their own permission. Admin can initiate a recharge without a merchant request.',
        ],

    ],
];
