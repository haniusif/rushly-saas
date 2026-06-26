<?php

/*
 * Salla OAuth / webhook credentials moved to per-tenant storage on
 * 2026-06-25. Each tenant manages their own Salla Partner app from
 * Admin → Integrations → Salla; values land in integration_settings.meta
 * and are read via sallaCreds('oauth_client_id'), etc.
 *
 * Only platform-wide defaults remain here.
 */
return [
    'api_base' => env('SALLA_API_BASE', 'https://api.salla.dev/admin/v2'),
];
