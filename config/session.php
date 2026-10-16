<?php

return [
    // Where sessions are kept: 'database' (the sessions table, works across servers), 'file'
    // (storage/sessions, fastest on one server), 'array' (memory, for tests), or the class
    // name of your own Pype\Session\SessionStore.
    'driver' => env('SESSION_DRIVER', 'database'),
    'table' => 'sessions',
    'path' => 'storage/sessions',   // for the 'file' driver

    // Minutes of inactivity before a session expires.
    'lifetime' => 120,

    // An unchanged session is only re-saved this often (seconds) to slide its expiry, so most
    // page views don't write to the store at all.
    'touch' => 60,

    // Cookie settings. Set SESSION_SECURE=true in production (HTTPS only).
    'cookie' => 'pype_session',
    'secure' => (bool) env('SESSION_SECURE', false),
    'same_site' => 'Lax',
    'domain' => null,

    // Paths that skip CSRF checks, e.g. '/webhooks/*'.
    'csrf_except' => [],
];
