<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Hash Driver
    |--------------------------------------------------------------------------
    |
    | SECURITY.md §2 requires Argon2id with bcrypt(12) as the accepted
    | fallback. Argon2id needs PASSWORD_ARGON2ID (libsodium), which any
    | modern PHP ships — but instead of crashing on a minimal XAMPP build,
    | the default degrades gracefully to bcrypt. The active driver is
    | asserted by tests/HashingTest (argon2id functional check) and must
    | be confirmed once on the target XAMPP (see ENVIRONMENT.md).
    | Stored hashes stay verifiable either way (algorithm id travels
    | inside the hash string), so mixed environments are safe.
    |
    */

    'driver' => env('HASH_DRIVER', defined('PASSWORD_ARGON2ID') ? 'argon2id' : 'bcrypt'),

    /*
    |--------------------------------------------------------------------------
    | Bcrypt Options
    |--------------------------------------------------------------------------
    */

    'bcrypt' => [
        'rounds' => env('BCRYPT_ROUNDS', 12),
        'verify' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Argon Options
    |--------------------------------------------------------------------------
    |
    | Shared by the `argon` and `argon2id` drivers. 64 MiB / 4 passes is
    | OWASP's interactive-login baseline and fast enough for login
    | throughput (<100 users) on shared hosting.
    |
    */

    'argon' => [
        'memory' => 65536,
        'threads' => 1,
        'time' => 4,
        'verify' => true,
    ],

];
