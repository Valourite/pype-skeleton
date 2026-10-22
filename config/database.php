<?php

return [
    // sqlite | mysql | pgsql. Migrations written with the schema builder run on all three.
    'default' => env('DB_CONNECTION', 'sqlite'),

    'connections' => [
        'sqlite' => [
            'driver' => 'sqlite',
            // Relative paths resolve from the project root. Use ':memory:' for throwaway DBs.
            'database' => env('DB_DATABASE', 'database/database.sqlite'),
            // Defaults: journal_mode=WAL, synchronous=NORMAL, foreign_keys=ON, busy_timeout=5000,
            // cache_size=-20000, temp_store=MEMORY. Every SQLITE_* variable in .env sets a pragma
            // (SQLITE_BUSY_TIMEOUT=10000); add your own here. null leaves one unset.
            'pragmas' => [
                ...env_prefixed('SQLITE_'),
            ],
        ],

        'mysql' => [
            'driver' => 'mysql',
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => (int) env('DB_PORT', 3306),
            'socket' => env('DB_SOCKET', ''),            // e.g. /run/mysqld/mysqld.sock (skips TCP)
            'database' => env('DB_DATABASE', 'pype'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => 'utf8mb4',
            'persistent' => (bool) env('DB_PERSISTENT', false),   // reuse connections across requests
        ],

        'pgsql' => [
            'driver' => 'pgsql',
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => (int) env('DB_PORT', 5432),
            'socket' => env('DB_SOCKET', ''),            // the socket's directory, e.g. /var/run/postgresql
            'database' => env('DB_DATABASE', 'pype'),
            'username' => env('DB_USERNAME', 'postgres'),
            'password' => env('DB_PASSWORD', ''),
            'persistent' => (bool) env('DB_PERSISTENT', false),
        ],
    ],

    'migrations' => 'database/migrations',

    // Counters for the Throttle stage: 'database' (the rate_limits table, works across servers),
    // 'apcu' (shared memory, no database work, one server only), or a class name of your own.
    'rate_limits' => [
        'driver' => env('RATE_LIMIT_DRIVER', 'database'),
        'table' => 'rate_limits',
    ],
];
