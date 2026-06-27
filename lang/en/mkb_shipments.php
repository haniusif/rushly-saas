<?php

return [
    'label'    => 'Shipments',
    'overview' => 'Create, edit, import, track and manage every shipment from your store. The most-used part of the panel.',
    'sub_pages' => [

        'list' => [
            'icon'    => 'Package',
            'label'   => 'Shipments list',
            'purpose' => 'Searchable table of every parcel you have created, with the current delivery status, tracking ID and the amount being collected on delivery.',
            'pages' => [
                ['path' => 'Index',          'desc' => 'Paginated list with filters: status, date range, tracking ID, customer phone. Row actions: view details, print label, view event log, clone, edit (when allowed), delete (when status is still Pending).'],
                ['path' => 'Status badges',  'desc' => 'Colour-coded badges show where the parcel is in its lifecycle — Pending, Picked, In transit, At hub, Out for delivery, Delivered, Returned, Cancelled.'],
                ['path' => 'Bulk select',    'desc' => 'Use the row checkboxes to print labels in batch or export the selection to Excel.'],
            ],
            'fields' => [
                'tracking_id', 'customer_name', 'customer_phone', 'city', 'area',
                'cash_collection', 'weight', 'delivery_charge', 'status',
                'shop_id', 'priority_type_id', 'created_at',
            ],
            'status_flow' => [
                ['label' => 'Pending',     'tone' => 'default'],
                ['label' => 'Picked',      'tone' => 'info'],
                ['label' => 'In transit',  'tone' => 'info'],
                ['label' => 'Delivered',   'tone' => 'ok'],
                ['label' => 'Returned',    'tone' => 'warn'],
                ['label' => 'Cancelled',   'tone' => 'bad'],
            ],
            'cross_links' => 'Each row links to the details page (full event timeline and proof of delivery), to the parcel logs, and — once delivered — to the matching invoice in Accounting.',
            'notes'       => 'Editing and deletion are only allowed while the parcel is still Pending. After pickup, the parcel is on the courier and changes must be requested via Support.',
        ],

        'create' => [
            'icon'    => 'Package',
            'label'   => 'Create shipment',
            'purpose' => 'Form to create a single shipment. The delivery charge, COD fee and VAT are calculated automatically from your pricing matrix the moment you pick a city + delivery type + weight.',
            'pages' => [
                ['path' => 'Form',         'desc' => 'Sections: Pickup point (your shop), Recipient (name, phone, city, area, address), Package (weight, category, priority), Charges (auto-calculated). Submit to save as Pending — the parcel is now in the dispatch queue.'],
                ['path' => 'Auto-pricing', 'desc' => 'As you change city / weight / delivery type, the delivery charge updates in real time. The COD charge depends on the cash_collection amount and your COD rate.'],
                ['path' => 'Priority',     'desc' => 'Mark the parcel as Liquid / Fragile or High priority — couriers handle these differently and the charge may differ.'],
            ],
            'fields' => [
                'shop_id', 'customer_name', 'customer_phone', 'customer_address',
                'city', 'area', 'weight', 'category', 'cash_collection',
                'invoice_no', 'priority_type_id', 'delivery_type_id',
                'note', 'exchange',
            ],
            'cross_links' => 'Pulls cities / areas from the global location service, pricing from your COD-charges + delivery-charges settings, and your registered pickup points (shops).',
            'notes'       => 'If your account is on Wallet mode, the form blocks submission when your wallet balance is below the calculated charges. Top up the wallet first under My Wallet.',
        ],

        'import' => [
            'icon'    => 'Package',
            'label'   => 'Import shipments',
            'purpose' => 'Bulk-create shipments by uploading a CSV. Best for daily batches from your e-commerce backend.',
            'pages' => [
                ['path' => 'Upload',  'desc' => 'Pick the CSV file. The system parses it server-side and shows a row-level preview before anything is committed.'],
                ['path' => 'Preview', 'desc' => 'Each row is validated against the expected columns and your pricing matrix. Invalid rows are highlighted with the exact reason (missing field, unknown city, weight out of range, etc.).'],
                ['path' => 'Confirm', 'desc' => 'Confirm to commit only the valid rows. Invalid rows are skipped — you fix them in the CSV and re-upload.'],
                ['path' => 'Template','desc' => 'Download the latest CSV template from the Index page header. Column order matters; do not rename headers.'],
            ],
            'fields' => ['csv_file', 'expected_headers', 'valid_rows', 'invalid_rows'],
            'cross_links' => 'Same downstream effect as Create — every imported row becomes a Pending parcel in the Shipments list.',
            'notes'       => 'Hard cap of 1000 rows per file. For larger batches, split the CSV. Imports run synchronously — keep the browser open until you see the success toast.',
        ],

        'details' => [
            'icon'    => 'Package',
            'label'   => 'Shipment details',
            'purpose' => 'Read-only view of a single shipment: full recipient info, financial breakdown, event timeline (every status change with timestamp and the courier who did it), proof-of-delivery photos and signature.',
            'pages' => [
                ['path' => 'Header',    'desc' => 'Tracking ID, current status, current courier (if assigned), pickup hub and delivery date.'],
                ['path' => 'Financial', 'desc' => 'COD amount, cash collected, delivery charge, COD charge, VAT, total payable / receivable.'],
                ['path' => 'Timeline',  'desc' => 'Chronological list of every parcel_event: status changes, courier assignments, NDR attempts, with timestamps and actor names.'],
                ['path' => 'Proof',     'desc' => 'After delivery, the courier uploads a signature image and delivery photos. Both appear here — click to enlarge.'],
                ['path' => 'Print',     'desc' => 'Re-print the shipping label or a delivery receipt from the action menu.'],
            ],
            'fields' => [
                'tracking_id', 'status', 'pickup_hub', 'current_courier',
                'delivery_date', 'signature_image', 'delivered_images',
                'cash_collection', 'cash_collected', 'delivery_charge',
                'cod_charge', 'vat_amount', 'total_payable',
            ],
            'cross_links' => 'Linked to Accounting (the invoice once delivered), to NDR (if any attempts failed), and to Reports (the parcel appears in Shipments report and Total summary).',
            'notes'       => 'If a parcel is stuck at the same status for several days, it may show up under "Abnormal shipments" on the admin side — they will reach out via Support if action is needed.',
        ],

    ],
];
