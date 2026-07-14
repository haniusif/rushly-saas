<?php

/**
 * Feature flags. Read via `config('features.<flag>')`. Default OFF for any
 * not-yet-stable subsystem — flipping a flag is the only thing that should
 * activate user-visible behavior.
 *
 * Convention: env key is `FEATURE_<UPPER_SNAKE_OF_KEY>`.
 */
return [

    /*
     * Commerce Integration Platform (app/Commerce/). Gates the admin UI,
     * webhook ingest routes, and any code path that resolves a
     * CommerceProvider. The migrations + module bindings load regardless so
     * the schema is in place before behavior flips on.
     */
    'commerce_layer' => (bool) env('FEATURE_COMMERCE_LAYER', false),

    /*
     * Two-step login: after a valid email+password, staff users (Admin,
     * SuperAdmin) are challenged with a 6-digit code emailed to them.
     * Merchants and deliverymen skip the challenge either way.
     */
    'login_otp' => (bool) env('FEATURE_LOGIN_OTP', false),

];
