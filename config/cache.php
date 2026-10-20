<?php

return [
    // Where cached values are kept:
    //   'file'     one PHP file per key in storage/cache; opcache serves hits from memory (default)
    //   'apcu'     shared memory, the fastest; needs the apcu extension, one server only (see README)
    //   'database' the cache table; shared by every server using the database
    //   'array'    memory for one request or test run; 'null' turns caching off
    // or the class name of your own Pype\Cache\Store.
    'driver' => env('CACHE_DRIVER', 'file'),

    // Every key starts with this, and `php bin/pype cache:clear` only removes keys that do,
    // so apps sharing a database or APCu don't clear each other's cache.
    'prefix' => env('CACHE_PREFIX', 'pype.'),

    'path' => 'storage/cache',   // for the 'file' driver: a directory of its own
    'table' => 'cache',          // for the 'database' driver
];
