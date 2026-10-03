<?php

return [
    'label'     => 'Support',
    'overview'  => 'Open and reply to support tickets. Track ticket status until your issue is resolved or closed.',
    'sub_pages' => [

        'tickets' => [
            'icon'    => 'MessageCircle',
            'label'   => 'Support tickets',
            'purpose' => 'List of every ticket you have opened with the operations team — subject, department, current status, last activity, and a quick reply thread.',
            'pages' => [
                ['path' => 'Index',  'desc' => 'Paginated list of your tickets. Columns: subject, department, service, status, last update.'],
                ['path' => 'Create', 'desc' => 'New ticket form: subject, department, service, description. Attach screenshots if useful.'],
                ['path' => 'View',   'desc' => 'Full thread for one ticket. Post a reply, attach a file, watch status changes from the operations side.'],
                ['path' => 'Edit',   'desc' => 'Edit a ticket only while it is still Pending. Once an agent picks it up, contact them through the reply thread instead.'],
            ],
            'fields' => [
                'subject', 'department_id', 'service', 'status',
                'description', 'attachments', 'replies',
            ],
            'status_flow' => [
                ['label' => 'Pending',    'tone' => 'default'],
                ['label' => 'Processing', 'tone' => 'info'],
                ['label' => 'Resolved',   'tone' => 'ok'],
                ['label' => 'Closed',     'tone' => 'default'],
            ],
            'cross_links' => 'For shipment-specific issues, include the tracking ID in the subject — the agent can jump straight to the shipment details and event timeline.',
            'notes'       => 'You only see your own tickets; agents see all tickets across merchants. Email notifications fire on every reply so check your inbox if you are not on the panel.',
        ],

    ],
];
