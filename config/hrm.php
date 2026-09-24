<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Display timezone
    |--------------------------------------------------------------------------
    |
    | All timestamps are STORED in UTC. This timezone is used ONLY for
    | presentation (Jalali dates, attendance times) in views, API
    | transformers and exports. Never use it for storage or comparison.
    |
    */

    'display_timezone' => env('DISPLAY_TIMEZONE', 'Asia/Tehran'),

    /*
    |--------------------------------------------------------------------------
    | Company display name
    |--------------------------------------------------------------------------
    |
    | Brand name as it must appear across the UI (with ZWNJ).
    |
    */

    'company_name' => env('HRM_COMPANY_NAME', 'قطعه‌رسان'),

    /*
    |--------------------------------------------------------------------------
    | Product name
    |--------------------------------------------------------------------------
    |
    | This product lives UNDER the master brand (see BRAND.md):
    | "GhatehResan — Human Resources", never a standalone brand.
    |
    */

    'product_name' => env('HRM_PRODUCT_NAME', 'منابع انسانی قطعه‌رسان'),

    /*
    |--------------------------------------------------------------------------
    | Authentication & account security (M2)
    |--------------------------------------------------------------------------
    |
    | Implements SECURITY.md §2. See AUTHENTICATION.md for the flows that
    | consume these values (login lockout, MFA, sessions, API tokens).
    |
    */

    'auth' => [

        // Password policy (App\Rules\StrongPassword).
        'password_min_length' => 12,
        'password_zxcvbn_min_score' => 3, // 0-4 scale, 3 = "safely unguessable"

        // Progressive lockout: 5th failed attempt locks 60s, 6th 300s,
        // 7th 900s, 8th and beyond 3600s. Reset on successful login.
        'lockout_threshold' => 5,
        'lockout_durations' => [60, 300, 900, 3600],

        // Absolute session lifetime (hours), independent of activity.
        // Enforced by EnsureSessionFresh on every authenticated route.
        'absolute_timeout_hours' => 12,

        // Roles that MUST complete MFA every session (SECURITY.md §2).
        'mfa_required_roles' => ['super-admin', 'hr-admin', 'finance'],

        // TOTP leeway in seconds; MUST stay below the 30s period.
        'mfa_totp_leeway' => 29,

        // Recovery codes issued per MFA method (single-use, hashed).
        'mfa_recovery_codes' => 8,

        // Consecutive wrong MFA codes before the session is dropped.
        'mfa_max_attempts' => 5,

        // API token lifetime for tokens issued via /api/v1/tokens.
        'api_token_ttl_days' => 30,
    ],

    /*
    |--------------------------------------------------------------------------
    | Web security headers (M2)
    |--------------------------------------------------------------------------
    |
    | Consumed by App\Http\Middleware\SecurityHeaders. The CSP deliberately
    | has no 'unsafe-inline': all JS/CSS ships as external Vite assets,
    | Alpine.js needs no inline handlers, and QR codes render as inline
    | <svg> elements (not data: images).
    |
    */

    'security' => [
        'csp' => "default-src 'self'; base-uri 'self'; frame-ancestors 'deny'; form-action 'self'; img-src 'self'; font-src 'self'; style-src 'self'; script-src 'self'",
    ],

];
