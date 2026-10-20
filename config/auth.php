<?php

return [
    // Table and class used for authenticated users. The class is filled from the row by
    // matching constructor parameter names to columns; the password column is never passed.
    'table' => 'users',
    'user' => App\Data\User::class,

    // Column used to log in, and the column holding the password hash.
    'identifier' => 'email',
    'password' => 'password',

    // How Authenticate (and the 'auth' stack) recognises a user, tried in order. The session
    // guard only works on routes in the 'web' stack; the token guard reads "Authorization: Bearer".
    // Add Pype\Auth\JwtGuard for JSON Web Tokens (see 'jwt' below), or your own Pype\Auth\Guard.
    'guards' => [
        Pype\Auth\SessionGuard::class,
        Pype\Auth\TokenGuard::class,
    ],

    // API tokens: table name and lifetime in minutes (null = never expire).
    'tokens_table' => 'api_tokens',
    'token_ttl' => 60 * 24 * 30,

    // JSON Web Tokens, used only if Pype\Auth\JwtGuard is in 'guards'. HS256 with a secret
    // (32+ random bytes) for tokens your app issues ($jwt->issue([...])); RS256 with a public key
    // (PEM, or 'kid' => PEM for several) for tokens from an identity provider.
    'jwt' => [
        'secret' => env('JWT_SECRET'),
        'public_key' => env('JWT_PUBLIC_KEY'),
        'private_key' => env('JWT_PRIVATE_KEY'),   // to issue RS256 tokens yourself
        'ttl' => 3600,                             // lifetime of issued tokens, seconds
        'issuer' => env('JWT_ISSUER'),             // checked when set
        'audience' => env('JWT_AUDIENCE'),         // checked when set
        'leeway' => 30,                            // seconds of clock difference allowed
        'user' => 'database',                      // load the user by `sub`, or 'claims' (no lookup)
        'only' => false,                           // true: a non-JWT bearer token is invalid, not left to TokenGuard
    ],

    // Password reset links (Pype\Auth\PasswordResets): how long a link works (minutes), and the
    // least time between two links for the same user (seconds).
    'reset_ttl' => 60,
    'reset_throttle' => 60,

    // "Remember me": a cookie that signs the user back in on this device after their session ends.
    // Set remember_table to null to turn it off.
    'remember_table' => 'remember_tokens',
    'remember_days' => 30,
    'remember_cookie' => 'pype_remember',
    'remember_secure' => (bool) env('SESSION_SECURE', false),   // HTTPS-only cookie in production
];
