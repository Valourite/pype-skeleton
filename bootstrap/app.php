<?php

declare(strict_types=1);

use Pype\Application;
use Pype\Support\Env;

require __DIR__ . '/../vendor/autoload.php';

Env::load(__DIR__ . '/../.env');

$app = new Application(
    basePath: dirname(__DIR__),
    config: Application::loadConfig(__DIR__ . '/../config'),
);

// Every file in routes/, subfolders too: api.php files are served under /api (names get api.),
// everything else as it is. Add routes/quotes/web.php and it loads; `php bin/pype make:routes quotes`.
$app->routesIn(__DIR__ . '/../routes');

return $app;
