<?php

return [
    'name' => env('APP_NAME', 'pype'),
    'debug' => (bool) env('APP_DEBUG', false),

    // Base for absolute links (url(..., absolute: true), signed_url()), e.g. https://example.com
    'url' => env('APP_URL', 'http://localhost:8000'),

    // Secret for signed URLs. `php bin/pype key:generate` sets it (create-project already did).
    'key' => env('APP_KEY', ''),

    // After changing APP_KEY, list the old one here (comma-separated in .env) so data encrypted
    // with it still decrypts. Signed links made with the old key stop working.
    'previous_keys' => array_values(array_filter(explode(',', (string) env('APP_PREVIOUS_KEYS', '')))),

    // What `php bin/pype serve` runs:
    //   'fpm'    PHP's built-in server for development (production: nginx + PHP-FPM, nothing to set)
    //   'swoole' a long-running OpenSwoole (or Swoole) server that boots the app once and serves
    //            production directly. Needs the openswoole or swoole extension; code changes need a restart.
    'server' => env('APP_SERVER', 'fpm'),
    // OpenSwoole/Swoole server settings, passed through as is over pype's defaults. Every SWOOLE_*
    // variable in .env sets one: SWOOLE_MAX_REQUEST=0 is 'max_request' => 0, SWOOLE_PACKAGE_MAX_LENGTH,
    // SWOOLE_MODE=base ... Short names: workers (worker_num, default 1 per CPU core) and
    // static_handler (true: the server's own static file handler, see GUIDE).
    'swoole' => [
        'mode' => 'process',       // process: a reactor holds the connections | base: each worker owns its own
        'max_request' => 100000,   // recycle each worker after this many requests (0 = never)
        ...env_prefixed('SWOOLE_'),
    ],

    // Reword pype's own errors (JSON or page), e.g. to match an API contract. Keys:
    // no_route, method_not_allowed, bad_json, payload_too_large, unsupported_media_type,
    // unauthenticated, invalid_token, invalid_token_payload, validation, csrf, throttled,
    // invalid_signature, unverified_email, invalid_param, server_error.
    'errors' => [
        // 'no_route' => 'Not found',
        // 'server_error' => 'Something went wrong',
    ],

    // Where View templates live (relative to the project root).
    'views' => 'resources/views',
    // Where Latte (.latte) and BladeOne (.blade.php) templates compile to, if you use them.
    'view_cache' => 'storage/views',

    // Providers wire services, stacks, global stages and outlets into the app.
    // Composer packages can ship their own Provider; list it here to enable it.
    'providers' => [
        Pype\Log\LogProvider::class,       // first, so it logs everything after it
        Pype\Database\DatabaseProvider::class,
        Pype\Auth\AuthProvider::class,
        Pype\Session\SessionProvider::class,
        Pype\View\ViewProvider::class,
        Pype\Mail\MailProvider::class,
        Pype\Cache\CacheProvider::class,
        Pype\Queue\QueueProvider::class,
        App\Providers\AppProvider::class,
    ],
];
