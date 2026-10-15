<?php

return [
    'name' => env('APP_NAME', 'pype'),
    'debug' => (bool) env('APP_DEBUG', false),

    // Where View templates live (relative to the project root).
    'views' => 'resources/views',

    // Providers wire services, stacks, global stages and outlets into the app.
    // Composer packages can ship their own Provider; list it here to enable it.
    'providers' => [
        Pype\Database\DatabaseProvider::class,
        Pype\Auth\AuthProvider::class,
        Pype\View\ViewProvider::class,
        App\Providers\AppProvider::class,
    ],
];
