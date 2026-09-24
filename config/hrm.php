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

];
