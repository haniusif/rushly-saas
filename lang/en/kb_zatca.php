<?php

return [
    'label'    => 'ZATCA E-Invoicing',
    'overview' => 'Saudi e-invoicing (ZATCA Phase 2): generate, sign and submit invoices, plus credential / endpoint settings.',
    'sub_pages' => [

        'invoices' => [
            'icon'    => 'Receipt',
            'label'   => 'Invoices',
            'purpose' => 'ZATCA Phase 1 generates compliant Saudi e-invoices with digitally-signed QR codes. This section lists generated / signed invoices with their compliance status, hash chain, and audit artifacts for verification and retrieval.',
            'pages' => [
                ['path' => 'Index',      'desc' => 'Paginated list with filters (status, type, date range) and search (invoice number / UUID / buyer). Shows invoice number, type, buyer, issue date, subtotal, VAT, total, status badge. Top stats cards: total / generated / failed / VAT collected.'],
                ['path' => 'Show',       'desc' => 'Detail view: UUID, hash, previous hash, sequence, issue date, buyer (name / VAT / address), monetary breakdown, currency, TLV payload (base64), scannable QR preview, error message (if failed), and actions (PDF / QR SVG / Regenerate).'],
                ['path' => 'Regenerate', 'desc' => 'Rebuilds the QR, hash chain and XML payload from the source merchant invoice. Preserves invoice_number and UUID; resets sequence and hash; status flips to Regenerated.'],
                ['path' => 'PDF',        'desc' => 'On-demand PDF with embedded QR, bilingual seller address, buyer details, line items, monetary breakdown and branding.'],
                ['path' => 'QR',         'desc' => 'SVG endpoint serving the digitally-signed QR at 6x6 resolution. Scannable by the ZATCA Fatoora mobile app.'],
            ],
            'fields' => [
                'uuid', 'invoice_number', 'invoice_type', 'status', 'issued_at',
                'buyer_name', 'buyer_vat_number', 'buyer_address',
                'subtotal', 'vat_rate', 'vat_amount', 'total_inclusive', 'currency',
                'qr_payload', 'qr_image_path', 'hash', 'previous_hash', 'sequence',
                'xml_payload', 'error_message', 'generated_at',
            ],
            'status_flow' => [
                ['label' => 'Pending',     'tone' => 'warn'],
                ['label' => 'Generated',   'tone' => 'ok'],
                ['label' => 'Regenerated', 'tone' => 'info'],
                ['label' => 'Failed',      'tone' => 'bad'],
            ],
            'cross_links' => 'Source merchant invoices via invoice_id; buyer Merchant via merchant_id; per-company ZATCA Settings; audit log of every generate / regenerate action.',
            'notes'       => 'Phase 1 implementation — QR / hash generation only, no Phase 2 gateway submission yet. VAT is split out of a VAT-inclusive total using the configured vat_rate. UUID persists across regenerations. The previous_hash chain provides an immutable ledger. Sandbox mode uses a NullGateway stub; production mode will route to the ZATCA API when ZatcaGateway is active.',
        ],

        'settings' => [
            'icon'    => 'FileText',
            'label'   => 'Settings',
            'purpose' => 'ZATCA Phase 1 configuration console for the tenant: seller identity, bilingual address, VAT / numbering parameters, and the sandbox-vs-production mode toggle. No CSID / PCSID (Phase 2 credentials) required yet.',
            'pages' => [
                ['path' => 'Index', 'desc' => 'Single form with a read-only "Configuration complete / incomplete" badge. Sections: Seller Info (bilingual names, 15-digit VAT, CR), Address (street, building, district, city, postal code, country — bilingual where applicable), Tax & Numbering (VAT rate, currency, mode toggle, invoice prefix). Two checkboxes: Enable ZATCA generation, Auto-generate on new invoices. Save → PUT + audit log.'],
            ],
            'fields' => [
                'seller_name_en', 'seller_name_ar', 'vat_number', 'cr_number',
                'address_street_en', 'address_street_ar', 'building_number',
                'district_en', 'district_ar', 'city_en', 'city_ar',
                'postal_code', 'country_code',
                'vat_rate', 'currency', 'mode', 'enabled', 'auto_generate',
                'invoice_prefix', 'last_invoice_counter', 'last_invoice_hash',
            ],
            'cross_links' => 'Every ZATCA invoice references this settings row via company_id and inherits vat_rate / currency / invoice_prefix. Audit log records every settings_updated action.',
            'notes'       => 'Saudi VAT format enforced (3XXXXXXXXXXXX3 — must start and end with 3, exactly 15 digits). Bilingual EN/AR fields required by ZATCA spec. VAT rate applies to all invoices unless overridden per-invoice. Mode (sandbox / production) is a hint — the actual endpoint is decided by the active gateway implementation. enabled = false blocks generation entirely. last_invoice_counter is incremented atomically; last_invoice_hash anchors the next invoice in the chain. country_code defaults to SA.',
        ],

    ],
];
