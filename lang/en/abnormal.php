<?php

return [
    'title'                       => 'Abnormal Shipments',
    'singular'                    => 'Abnormal Shipment',

    // Severity summary cards
    'stalled_3_days'              => 'Stalled 3+ days',
    'stalled_5_days'              => 'Stalled 5+ days',
    'stalled_7_days_critical'     => 'Stalled 7+ days (critical)',
    'closed_as_lost'              => 'Closed as Lost',

    // Filters
    'duration'                    => 'Duration',
    'any_severity'                => 'Any severity',
    'any_investigator'            => 'Any investigator',
    'detection_threshold'         => 'Detection threshold',
    'days'                        => 'days',

    // Table headers
    'last_event'                  => 'Last Event',
    'stale_days'                  => 'Stale Days',
    'severity'                    => 'Severity',
    'no_abnormal'                 => 'No abnormal shipments. The hourly cron will surface them as they appear.',

    // Show page
    'detected'                    => 'Detected',
    'assigned_to'                 => 'Assigned To',
    'nobody_yet'                  => 'Nobody yet',
    'stale_progress'              => 'Stale progress',
    'event_timeline'              => 'Event Timeline',
    'no_events'                   => 'No parcel events on file.',
    'investigation'               => 'Investigation',
    'assign_to_investigator'      => 'Assign to investigator',
    'assign'                      => 'Assign',
    'take_action'                 => 'Take action',
    'create_ndr'                  => 'Create NDR',
    'customer_contact_logged'     => 'Customer contact attempt logged.',
    'log_customer_contact'        => 'Log customer contact',
    'escalate'                    => 'Escalate',
    'close_as_lost_confirm'       => 'Close-as-Lost requires two supervisors. Proceed?',
    'close_as_lost'               => 'Close as Lost',
    'resolution_note_placeholder' => 'Resolution note (optional)',
    'mark_resolved'               => 'Mark Resolved',
    'notes'                       => 'Notes',

    // Settings page
    'detection'                   => 'Detection',
    'detection_after_inactivity'  => 'Flag a shipment as abnormal after no activity for…',
    'default_3_days'              => 'Default: 3 days',
    'auto_escalation_threshold'   => 'Auto-escalation threshold',
    'auto_escalation_hint'        => 'When stale_days hits this, push notify company admins.',
    'exclude_from_detection'      => 'Exclude from detection',
    'public_holidays'             => 'Public holidays',
    'public_holidays_hint'        => 'Days marked as holiday do not count toward stale_days.',
    'pending_customs'             => 'Pending customs clearance',
    'pending_customs_hint'        => 'Held at customs is not abnormal.',
    'sender_hold'                 => 'On hold by sender request',
    'sender_hold_hint'            => 'Merchant explicitly paused the shipment.',
    'notifications'               => 'Notifications',
    'daily_digest_8am'            => 'Daily digest at 8:00 AM',
    'daily_digest_hint'           => 'Push notification to supervisors summarising all open abnormals.',
    'save_settings'               => 'Save Settings',
];
