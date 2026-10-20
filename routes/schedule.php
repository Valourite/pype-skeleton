<?php

/**
 * Scheduled tasks. Run them from cron, every minute:
 *
 *   * * * * * cd /path/to/app && php bin/pype schedule:run >> /dev/null 2>&1
 *
 * or with `php bin/pype schedule:work` where there is no cron. `php bin/pype schedule:list`
 * shows what's here. This file is only loaded by those commands, never for web requests.
 *
 * @var Pype\Schedule\Schedule $schedule
 * @var Pype\Application $app
 */

use Pype\Cache\Store;
use Pype\Session\SessionStore;

// $schedule->timezone('Africa/Johannesburg');   // for the tasks below (default: PHP's)

// Housekeeping: expired sessions and cache entries are otherwise only skipped, never removed.
$schedule->call(fn (SessionStore $sessions) => $sessions->gc())->hourly()->name('sessions:gc');
$schedule->call(function (Store $cache) {
    if (method_exists($cache, 'pruneExpired')) {
        $cache->pruneExpired();   // file and database stores
    }
})->daily()->name('cache:prune');

// Examples:
// $schedule->command('reports:send', ['--to=team@example.com'])->weeklyOn(1, '08:00');
// $schedule->job(new App\Jobs\SyncRates())->everyFifteenMinutes()->withoutOverlapping();
