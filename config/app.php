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
    //   'swoole' a long-running OpenSwoole server that boots the app once and serves production
    //            directly. Needs the openswoole extension; code changes need a restart.
    'server' => env('APP_SERVER', 'fpm'),
    'swoole' => [
        'workers' => env('SWOOLE_WORKERS'),   // default: 2 per CPU core
        'max_request' => 10000,               // recycle each worker after this many requests
    ],

    // Where View templates live (relative to the project root).
    'views' => 'resources/views',

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
        App\Providers\AppProvider::class,
    ],
];
