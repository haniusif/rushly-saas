<?php

return [
    'title'    => 'Mobile Apps',
    'subtitle' => 'Eight role-specific Flutter apps that consume the /api/v10/* endpoints of this platform.',

    'group_role_apps' => 'Role-specific apps',
    'footer_note'     => 'All eight apps share the same tenant-aware install flow — enter your workspace URL, sign in with your existing credentials, and each app scopes itself to your role.',

    'driver_title'      => 'Driver App',
    'driver_audience'   => 'Delivery drivers',
    'driver_desc'       => 'Assigned parcels, delivery outcomes (delivered / partial / not-delivered with photo), NDR, earnings, cash reconciliation, live tracking map and route-optimised runsheet.',

    'merchant_title'    => 'Merchant App',
    'merchant_audience' => 'Merchants / shop owners',
    'merchant_desc'     => 'Parcel CRUD, bulk CSV import, tracking map, shops, payments (accounts + requests + statements PDF), invoices, fraud, NDR, store connections and reports.',

    'admin_title'       => 'Admin App',
    'admin_audience'    => 'Back-office (admin / hub / incharge)',
    'admin_desc'        => 'Dashboard, parcels, drivers, merchants (with onboarding approval), hubs, payouts, support, fraud, driver-assignment map, hub cash, WMS lookup + GRN + cycle count + damage, and 3PL assignment.',

    'supervisor_title'    => 'Supervisor App',
    'supervisor_audience' => 'Field supervisors',
    'supervisor_desc'     => 'Live drivers list + detail, unassigned parcels + assign, per-driver performance reports with date range, and aggregated exceptions feed (open NDRs, stuck parcels, returning to courier).',

    'warehouse_title'    => 'Warehouse App',
    'warehouse_audience' => 'Warehouse staff',
    'warehouse_desc'     => 'Receive (GRN scan + stock lookup), Pick & Pack (fulfillment queue with SLA), Inventory (cycle count + damage + adjust) and Dispatch (READY queue + AWB scan hand-off).',

    'sorting_title'    => 'Sorting App',
    'sorting_audience' => 'Hub sorting operations',
    'sorting_desc'     => 'Scan In, Sort (auto-drop into destination-hub bag), session-scoped Bags with contents, and Routes (grouped-by-hub dispatch with bulk TRANSFER_TO_HUB).',

    'fleet_title'      => 'Fleet App',
    'fleet_audience'   => 'Long-haul fleet drivers',
    'fleet_desc'       => 'Trips (start/end with odometer + pre-trip inspection), assigned vehicle info, fuel fill-up log and maintenance reporting.',

    'scanner_title'    => 'Scanner App',
    'scanner_audience' => 'Any pipeline staff',
    'scanner_desc'     => 'Universal AWB scanner with status-aware action strip (e.g. TRANSFER_TO_HUB → "Received by hub") and device-local scan history.',
];
