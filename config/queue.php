<?php

return [
    // Where queued jobs go:
    //   'sync'     run right away, in the request (development: nothing to start)
    //   'database' the jobs table; run `php bin/pype queue:work` to process them (production)
    //   'array'    kept in memory, for tests that check what was pushed
    // or the class name of your own Pype\Queue\Queue.
    'driver' => env('QUEUE_DRIVER', 'sync'),

    'default' => 'default',          // the queue jobs go to unless push() names one
    'table' => 'jobs',
    'failed_table' => 'failed_jobs',

    // A job reserved longer than this (its worker died) is handed out again. Keep it above
    // your slowest job and queue:work's --timeout (60 s by default).
    'retry_after' => 90,
];
