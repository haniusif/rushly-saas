<?php

return [
    'label'    => 'Shipments',
    'overview' => 'End-to-end parcel lifecycle: create, edit, dispatch, bulk-action, plus NDR (non-delivery) tracking and stalled-shipment investigation.',
    'sub_pages' => [

        'parcels' => [
            'icon'    => 'Package',
            'label'   => 'Parcels',
            'purpose' => 'Central hub for managing the shipment lifecycle from creation through delivery — tracks each parcel through pickup, warehouse handling, hub transfer, delivery assignment, and final delivery or return. Supports COD collections, multi-carrier 3PL integration, and WMS fulfillment workflows.',
            'pages' => [
                ['path' => 'Index',       'desc' => 'Filterable table (tracking ID, customer, merchant, status, courier, date) with per-row dropdowns (view, print label, logs, clone, edit, delete, status change) and bulk selection.'],
                ['path' => 'Create',      'desc' => 'Shipment form: merchant/shop, recipient, category/weight/packaging, priority (normal/high/liquid-fragile). Auto-calculates delivery / COD / VAT charges from merchant + delivery-category matrix.'],
                ['path' => 'Edit',        'desc' => 'Modify all fields except tracking_id; recalculates charges on save.'],
                ['path' => 'Details',     'desc' => 'Read-only view with sender / recipient cards, financial breakdown, event timeline (status transitions with actor/hub/timestamp), proof-of-delivery attachments and 3PL AWB sync.'],
                ['path' => 'Logs',        'desc' => 'Pipeline visualisation (Pending → In Progress → Warehouse → Dispatch → Delivered / Returned) with the full ParcelEvent log.'],
                ['path' => 'Clone',       'desc' => 'Duplicate an existing parcel — useful for recurring orders.'],
                ['path' => 'Print label', 'desc' => 'Barcode / AWB label PDF (also available in batch via Bulk Action).'],
            ],
            'fields' => [
                'tracking_id', 'code', 'customer_name', 'customer_phone', 'customer_address',
                'city', 'area', 'merchant_name', 'pickup_address', 'invoice_no',
                'cash_collection', 'weight', 'category', 'priority_type_id',
                'delivery_charge', 'cod_charge', 'vat_amount', 'total_delivery_amount',
                'status', 'number_of_attempts',
            ],
            'status_flow' => [
                ['label' => 'pending',                 'tone' => 'default'],
                ['label' => 'pickup_assign',           'tone' => 'info'],
                ['label' => 'received_warehouse',      'tone' => 'info'],
                ['label' => 'transfer_to_hub',         'tone' => 'info'],
                ['label' => 'delivery_man_assign',     'tone' => 'info'],
                ['label' => 'delivered',               'tone' => 'ok'],
                ['label' => 'returned_merchant',       'tone' => 'bad'],
                ['label' => 'cancelled',               'tone' => 'bad'],
            ],
            'cross_links' => 'Feeds the NDR module on failed attempts; escalates to Abnormal Shipments when stalled. Hands off to WMS Fulfillment when parcels carry wms_* statuses. Generates merchant invoices for settlement. Integrates with Panda / Zajel / Aramex / Jet / Logestechs for multi-carrier handoff.',
            'notes'       => 'Parcel statuses have allowed-state transitions enforced server-side. 3PL integration auto-fetches AWBs from Panda. Subscription tier gates parcel count. Wallet activation checks merchant balance before store. Bulk creation supports CSV import with row-level preview & validation.',
        ],

        'bulk-action' => [
            'icon'    => 'Wand2',
            'label'   => 'Bulk action',
            'purpose' => 'Apply a state-change operation to many parcels at once (assign 100 parcels to one courier, transfer 50 to a hub, return a batch to a merchant). Designed to cut clicks for high-volume 3PL operations.',
            'pages' => [
                ['path' => 'Bulk Action form', 'desc' => 'Paste up to 500 tracking IDs or numeric IDs; selected parcels are grouped by their current status; pick an action (assign-pickup, transfer-to-hub, assign-deliveryman, return-to-merchant, escalate-to-ndr).'],
                ['path' => 'Apply',            'desc' => 'POST that executes the chosen action — toast on success, inline error on validation failure (e.g. no courier selected).'],
            ],
            'fields' => ['parcel_ids', 'action_type', 'action_data'],
            'cross_links' => 'Triggered from the parcel Index (checkbox row selection). Feeds into the NDR module via the escalate-to-ndr action.',
            'notes'       => 'Accepts RLxxxxxx tracking format or numeric IDs. Duplicates in the paste are de-duped automatically. Pre-apply summary shows counts grouped by status so you can see the operational impact before committing. Capped at 500 parcels per request for performance.',
        ],

        'ndr' => [
            'icon'    => 'AlertTriangle',
            'label'   => 'NDR (Non-Delivery Reports)',
            'purpose' => 'Track and resolve failed delivery attempts. Each NDR captures the failure reason, the attempting driver, and the action taken (reschedule, return to merchant, transfer to hub, or escalate). Supports up to 3 attempts per parcel before auto-escalation.',
            'pages' => [
                ['path' => 'Index',          'desc' => 'Sortable table (attempt#, tracking_id, reason, deliveryman, status, created). Summary KPIs (today / open / in_progress / resolved / return_rate %). Filters: status, failure reason, deliveryman, date. Excel export.'],
                ['path' => 'Create',         'desc' => 'Record a failure for a parcel: failure_reason dropdown, optional driver notes / photo, next_attempt_date, deliveryman. Enforces one open NDR per parcel per day and a 3-attempt cap.'],
                ['path' => 'Show',           'desc' => 'Detail view with attempt count, reason, notes/photo, action buttons (take action, resolve) and an auto-escalation timer.'],
                ['path' => 'Update Action',  'desc' => 'Apply a resolution (reschedule / return_to_merchant / transfer_hub / escalate); updates NDR status to in_progress.'],
                ['path' => 'Resolve',        'desc' => 'Mark resolved — rejected if 3 pending attempts already exist for the same parcel.'],
            ],
            'fields' => [
                'parcel_id', 'deliveryman_id', 'attempt_number', 'failure_reason',
                'driver_notes', 'driver_photo', 'customer_notified', 'action_taken',
                'next_attempt_date', 'resolved_by', 'resolved_at', 'status', 'abnormal_shipment_id',
            ],
            'status_flow' => [
                ['label' => 'open',        'tone' => 'default'],
                ['label' => 'in_progress', 'tone' => 'info'],
                ['label' => 'resolved',    'tone' => 'ok'],
                ['label' => 'returned',    'tone' => 'warn'],
            ],
            'cross_links' => 'Links back to Parcel via parcel_id. Auto-escalates to Abnormal Shipments after 3 failed attempts. Driver assignment ties to Couriers. Hub assignment ties to Hubs.',
            'notes'       => 'Hard limits: max 3 attempts per parcel, one open NDR per parcel per day. Reasons come from the NdrFailureReason enum (9 values). Driver photos land in /uploads/ndr/. An NDR can carry abnormal_shipment_id to group it under an investigation.',
        ],

        'abnormal' => [
            'icon'    => 'AlertOctagon',
            'label'   => 'Abnormal shipments',
            'purpose' => 'Proactively detect and resolve stalled shipments (no event for N+ days). Assigns an investigator, supports dual-approval close-as-lost, and integrates NDR escalations.',
            'pages' => [
                ['path' => 'Index',        'desc' => 'Table (tracking_id, customer, last_event diffForHumans, stale_days, severity, status, investigator). Summary cards (stalled 3 / 5 / 7+ days, closed-as-lost). Filters: min_days, severity, status, investigator.'],
                ['path' => 'Show',         'desc' => 'Investigation page: parcel details, stale progress bar, detected_at / escalated_at / assigned_to, resolution_note log, and action buttons (assign, create NDR, log contact, escalate, close-as-lost, resolve). 15 most-recent parcel events shown inline.'],
                ['path' => 'Settings',     'desc' => 'Config: threshold_days (detection trigger), auto_escalation_days, exclude_holidays / customs / on_hold, daily_digest_enabled.'],
                ['path' => 'Assign',       'desc' => 'PUT to set assigned_to (investigator user_id).'],
                ['path' => 'Take Action',  'desc' => 'POST for an investigation step: reassign_deliveryman, create_ndr, log_contact (timestamped note), escalate (sets escalated_at, status=investigating), close_lost (dual-approval).'],
                ['path' => 'Resolve',      'desc' => 'Mark resolved (status=resolved, resolved_at=now, resolved_by=current user). Requires a resolution_note.'],
            ],
            'fields' => [
                'parcel_id', 'detected_at', 'last_event_at', 'stale_days', 'severity',
                'assigned_to', 'status', 'resolution_note', 'resolved_by', 'escalated_at', 'resolved_at',
            ],
            'status_flow' => [
                ['label' => 'open',          'tone' => 'default'],
                ['label' => 'investigating', 'tone' => 'info'],
                ['label' => 'resolved',      'tone' => 'ok'],
                ['label' => 'closed_lost',   'tone' => 'bad'],
            ],
            'cross_links' => 'Detection triggers off ParcelEvent gaps; auto-creates from NDR after 3 failed attempts. Links back to Parcel for the full event timeline. Resolution feeds back into Parcel status history.',
            'notes'       => 'Severity is auto-calculated from stale_days: warning (3–4 d), danger (5–6 d), critical (7+ d). stale_days excludes holiday/customs/on-hold periods if configured. Close-as-Lost is dual-approval: the first user flags the record, a second user from a different account must confirm before status flips to closed_lost. A daily digest email can notify the ops team.',
        ],

    ],
];
