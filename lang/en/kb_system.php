<?php

return [
    'label'    => 'System',
    'overview' => 'Operational telemetry: activity logs of who-did-what across the admin panel.',
    'sub_pages' => [

        'logs' => [
            'icon'    => 'History',
            'label'   => 'Activity Logs',
            'purpose' => 'Records every admin and system action (create / update / delete) across modules with causer identity, timestamped changes, and subject references. Essential for audit trails, compliance, and troubleshooting unauthorised or erroneous modifications.',
            'pages' => [
                ['path' => 'Index', 'desc' => 'Paginated table (15 rows/page) of all activity log entries, filtered by current tenant. Columns: log_name, event type (coloured pills: created/green, updated/amber, deleted/red), subject_type, description, causer (user name), created_at. Ordered most-recent first. Open View for detailed change inspection.'],
                ['path' => 'View',  'desc' => 'Single-log detail showing a before/after table (property, new value, old value). Properties are JSON-decoded from properties.attributes and properties.old; labels come from lang/{locale}/ActivityLogs.php.'],
            ],
            'fields' => [
                'log_name', 'description', 'subject_type', 'subject_id',
                'causer_type', 'causer_id', 'event', 'properties',
                'batch_uuid', 'created_at', 'updated_at',
            ],
            'cross_links' => 'Every model with the Spatie ActivityLog trait emits entries here — User, Parcel, Merchant, Hub, Support, Account, Payment, Role, GeneralSettings, NotificationSettings, NewsOffer, Salary, Upload, AssetCategory, Packaging, ToDo and more. Entries are grouped by the log_name bucket.',
            'notes'       => 'Retention is 365 days (config: delete_records_older_than_days). The activity:clean Artisan command prunes anything older. All queries scoped by company_id (multi-tenant isolation). Properties stored as JSON; diffs accessible via $log->changes(). Permission gate: log_read for the index. No IP / user_agent columns stored natively.',
        ],

    ],
];
