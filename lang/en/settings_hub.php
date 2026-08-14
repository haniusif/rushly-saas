<?php

return [
    'title'    => 'Settings',
    'subtitle' => 'Configure every aspect of your platform from a single hub.',

    'group_identity'       => 'Identity & branding',
    'group_operations'     => 'Operations',
    'group_communications' => 'Communications',
    'group_finance'        => 'Finance & invoicing',

    'general_desc'         => 'Tenant identity, branding, logos, theme and locale defaults.',
    'social_desc'          => 'Enable Google / Facebook / GitHub sign-in for the public app.',
    'integrations_desc'    => 'Manage Aramex, JET, Twilio, SMTP and other third-party credentials.',
    'mobile_apps_desc'     => 'Companion Flutter apps for drivers, merchants, admins, warehouse and more.',

    'delivery_category_desc' => 'Define how shipments are categorised across the operations flow.',
    'delivery_charge_desc'   => 'Configure per-zone or per-weight delivery pricing tables.',
    'delivery_type_desc'     => 'Standard, express, same-day — define your service levels.',
    'liquid_desc'            => 'Flag liquid and fragile parcel handling rules.',
    'packaging_desc'         => 'Maintain packaging units used at fulfillment.',
    'assets_desc'            => 'Categorise warehouse assets and equipment.',
    'label_desc'             => 'Pick the shipping AWB label layout (5 carrier-style templates).',

    'sms_settings_desc'      => 'Connect your SMS gateway (Twilio, Vonage, etc.).',
    'sms_send_desc'          => 'Per-event SMS templates and dispatch rules.',
    'notifications_desc'     => 'In-app and push notification preferences.',
    'googlemap_desc'         => 'Google Maps API keys for live tracking and routing.',

    'invoice_gen_desc'       => 'Manually trigger merchant billing invoice generation.',
    'payout_desc'            => 'Payment gateways (Stripe, Razorpay, PayPal) used to receive payouts.',
    'zatca_desc'             => 'ZATCA Phase 1 — Saudi e-invoicing seller identity, VAT and QR settings.',
];
