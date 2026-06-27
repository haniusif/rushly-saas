<?php

return [
    'label'    => 'Productivity',
    'overview' => 'Internal tools: todo list, support tickets, news/offers, push notifications and fraud signals.',
    'sub_pages' => [

        'todo' => [
            'icon'    => 'ListChecks',
            'label'   => 'Todo',
            'purpose' => 'Internal task management for staff. Tracks todos with assignment, due dates, and status progression from pending to completion.',
            'pages' => [
                ['path' => 'Index',         'desc' => 'List of todos with status badges, assignee names, due dates; paginated 10 per page.'],
                ['path' => 'Create (modal)','desc' => 'Inline form to add a new todo: title, description, assignee, due date.'],
                ['path' => 'Edit (modal)',  'desc' => 'Update existing todo title, description, assignee, due date.'],
                ['path' => 'Status Update', 'desc' => 'Mark as processing or completed with optional note.'],
            ],
            'fields' => ['title', 'description', 'user_id', 'date', 'status', 'note', 'company_id'],
            'status_flow' => [
                ['label' => 'Pending (1)',    'tone' => 'warn'],
                ['label' => 'Processing (2)', 'tone' => 'info'],
                ['label' => 'Completed (3)',  'tone' => 'ok'],
            ],
            'cross_links' => 'Support tickets reference staff assignments. Push Notifications can target todo owners. News/Offers are created by staff managing their todo workload.',
            'notes'       => 'Company-wide scoped. Status changes (Processing, Completed) capture an optional note. Modal-based UI for create/edit — there is no detail/show page. Activity log tracks title, description, user, and date changes.',
        ],

        'support' => [
            'icon'    => 'MessageCircle',
            'label'   => 'Support',
            'purpose' => 'Two-way support-ticket system between admin and merchants. Tracks issues by priority, service and department; supports threaded chat replies.',
            'pages' => [
                ['path' => 'Index',         'desc' => 'Paginated list of tickets (user, subject, priority, status, next-action dropdown for status progression).'],
                ['path' => 'Create',        'desc' => 'New ticket: department, service, priority, subject, description, optional attachment, date.'],
                ['path' => 'Edit',          'desc' => 'Update ticket fields (department, service, priority, subject, description, date, attachment).'],
                ['path' => 'View',          'desc' => 'Full ticket with conversation thread (support_chats); shows all messages from both sides.'],
                ['path' => 'Status Update', 'desc' => 'Dropdown actions to move the ticket: Pending → Processing → Resolved or Closed.'],
            ],
            'fields' => [
                'user_id', 'department_id', 'service', 'priority', 'subject',
                'description', 'date', 'attached_file', 'status',
                'support_chat.message', 'support_chat.attached_file',
            ],
            'status_flow' => [
                ['label' => 'Pending (1)',    'tone' => 'warn'],
                ['label' => 'Processing (2)', 'tone' => 'info'],
                ['label' => 'Resolved (3)',   'tone' => 'ok'],
                ['label' => 'Closed (4)',     'tone' => 'bad'],
            ],
            'cross_links' => 'Attachments reference the Uploads table. Department lookup scoped to active departments. Users can be admin (superadmin filter) or company-scoped. Replies create SupportChat records.',
            'notes'       => 'Department routing supported. Priority levels: low / medium / high with colour-coded UI. Attached-file handling on both ticket and chat messages. Bidirectional messaging via SupportChat. Status flow is linear: Pending → Processing → (Resolved or Closed). Chat history shown reverse-chronologically.',
        ],

        'news' => [
            'icon'    => 'Newspaper',
            'label'   => 'News / Offers',
            'purpose' => 'Marketing announcements and promotional offers pushed to merchant dashboards. Admin creates entries with optional featured image; status controls visibility.',
            'pages' => [
                ['path' => 'Index',  'desc' => 'List of news/offers: title, image preview, status (active/inactive), date. CRUD actions per row.'],
                ['path' => 'Create', 'desc' => 'Add a news entry: title, description (rich text), image upload, date, status.'],
                ['path' => 'Edit',   'desc' => 'Update title, description, image, date, status.'],
            ],
            'fields' => ['title', 'description', 'date', 'file', 'status', 'author', 'company_id'],
            'status_flow' => [
                ['label' => 'Active (1)',   'tone' => 'ok'],
                ['label' => 'Inactive (0)', 'tone' => 'bad'],
            ],
            'cross_links' => 'Image stored in the Uploads table by foreign key. author tracks the user_id. Company-scoped visibility. The merchant panel reads from the same data via MerchantNewsOfferController.',
            'notes'       => 'description stored as longText; stripped of HTML tags for previews (max 140 chars). Date is optional; status defaults to Active. Image retrieval uses Upload.original path with a default-logo fallback. Activity log tracks title, description, date.',
        ],

        'push-notifications' => [
            'icon'    => 'BellRing',
            'label'   => 'Push Notifications',
            'purpose' => 'Send real-time push notifications to merchant mobile apps and web browsers. Target by role, specific user, or all users; integrates with the FCM service.',
            'pages' => [
                ['path' => 'Index',  'desc' => 'List of sent notifications: title, description preview, audience label (user name or role), image, timestamp.'],
                ['path' => 'Create', 'desc' => 'Compose: title, description, role/user targeting dropdown, optional image upload.'],
            ],
            'fields' => ['title', 'description', 'user_id', 'merchant_id', 'type', 'image_id', 'company_id'],
            'cross_links' => 'Targets users by user_type enum (Admin, Merchant, Deliveryman, etc.) or specific user_id. Images stored in Uploads. Delivery via PushNotificationService — FCM for mobile + web_token for browser.',
            'notes'       => 'Audience logic — type=all sends to all non-Admin users, type=specific user_id sends to one user, type=(role) sends to all users of that role. No edit/update — notifications are fire-and-forget. Delete available to clear history. Service layer handles actual delivery.',
        ],

        'fraud' => [
            'icon'    => 'ShieldAlert',
            'label'   => 'Fraud',
            'purpose' => 'Fraud-signal registry for identifying suspicious deliveries. Staff log fraud cases with customer contact, package tracking, and incident details for pattern detection.',
            'pages' => [
                ['path' => 'Index',  'desc' => 'List of fraud reports: phone, name, tracking ID, details preview. CRUD actions per row.'],
                ['path' => 'Create', 'desc' => 'Add a fraud report: phone, name, tracking_id, details (description).'],
                ['path' => 'Edit',   'desc' => 'Update all fields (phone, name, tracking_id, details).'],
            ],
            'fields' => ['phone', 'name', 'tracking_id', 'details', 'created_by', 'company_id'],
            'cross_links' => 'created_by logs the reporting staff member. Company-scoped. tracking_id links back to a parcel for context (no FK enforced).',
            'notes'       => 'No status workflow — all reports are equal priority and the system is a simple historical registry. details stored as rich HTML, stripped for previews (max 140 chars). Activity log includes the created_by user name.',
        ],

    ],
];
