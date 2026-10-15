<?php

return [
    // Where sessions are kept: 'database' (the sessions table), 'array' (memory, for tests),
    // or the class name of your own Pype\Session\SessionStore.
    'driver' => env('SESSION_DRIVER', 'database'),
    'table' => 'sessions',

    // Minutes of inactivity before a session expires.
    'lifetime' => 120,

    // Cookie settings. Set SESSION_SECURE=true in production (HTTPS only).
    'cookie' => 'pype_session',
    'secure' => (bool) env('SESSION_SECURE', false),
    'same_site' => 'Lax',
    'domain' => null,

    // Paths that skip CSRF checks, e.g. '/webhooks/*'.
    'csrf_except' => [],
];
