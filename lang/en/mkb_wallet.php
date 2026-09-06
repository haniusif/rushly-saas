<?php

return [
    'label'     => 'My Wallet',
    'overview'  => 'Top up your prepaid wallet, view transaction history and check the current balance used to pay for shipments.',
    'sub_pages' => [

        'my-wallet' => [
            'icon'    => 'Wallet',
            'label'   => 'My wallet',
            'purpose' => 'A prepaid balance you can top up and spend on shipment fees. Useful when your account is configured as Wallet-mode (charges are deducted up front rather than collected from COD).',
            'pages' => [
                ['path' => 'Wallet home', 'desc' => 'Current balance, last recharge, total spent and the most recent transactions.'],
                ['path' => 'Recharge',    'desc' => 'Pick a recharge amount and pay through the configured online gateway. The amount lands in your balance once the gateway returns success.'],
                ['path' => 'History',     'desc' => 'Full ledger: every recharge, every spend, every refund. Each row shows the date, source, amount and resulting balance.'],
            ],
            'fields' => [
                'balance', 'last_recharge_amount', 'last_recharge_at',
                'total_spent', 'pending_recharge',
            ],
            'status_flow' => [
                ['label' => 'Pending',  'tone' => 'default'],
                ['label' => 'Approved', 'tone' => 'ok'],
                ['label' => 'Rejected', 'tone' => 'bad'],
            ],
            'cross_links' => 'When you Create a shipment and your account is in Wallet mode, the wallet is debited the moment the shipment is saved. If the balance is below the calculated charges, the form blocks the submission and links you here.',
            'notes'       => 'Recharges are not refundable as cash — the credit stays in the wallet and is consumed by future shipments. Refunds for cancelled shipments are credited back automatically.',
        ],

    ],
];
