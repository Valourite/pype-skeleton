<?php

declare(strict_types=1);

// The development screens at /_pype (Pype\Debug\DebugProvider):
//   /_pype/routes     every route taken apart into its stages, what each needs and puts
//   /_pype/requests   what each request did, stage by stage, with times and queries
//   /_pype/sandbox    a throw-away SQLite database built from your migrations and seeder
//                     (php bin/pype sandbox:build): send a request into it, see the rows it changed
return [
    // On only when APP_DEBUG=true, and never in production. Outside debug they are only
    // registered if enabled AND given stages that guard them, e.g.
    // ['web', 'auth', App\Stages\EnsureAdmin::class]: they show how the app is put together.
    'enabled' => (bool) env('APP_DEBUG', false),
    'path' => '/_pype',
    'stages' => [],

    // The newest requests are kept as files. Off while your tests run (APP_ENV=testing).
    'requests' => ['keep' => 100, 'dir' => 'storage/debug/requests'],

    // The seeder that fills the sandbox (database/seeders), or false for empty tables.
    // Under PHP-FPM, add 'php' => '/usr/bin/php' if `php` isn't on the web server's PATH.
    'sandbox' => ['seeder' => 'DatabaseSeeder', 'dir' => 'storage/debug/sandbox'],
];
