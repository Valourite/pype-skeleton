<?php

declare(strict_types=1);

namespace App\Providers;

use PDOException;
use Pype\Application;
use Pype\Http\Response;
use Pype\Provider;
use Pype\Stages\Cors;
use Pype\Stages\JsonBody;
use Pype\Stages\RequestId;

final class AppProvider implements Provider
{
    public function register(Application $app): void
    {
        // Global stages wrap every request, including 404s and errors. Order = outermost first.
        $app->through(
            new RequestId(),
            new Cors(origins: ['*']),
            new JsonBody(maxBytes: 1024 * 1024),
        );

        // Named stacks: reusable stage sequences, referenced by name in any route or group.
        // ('auth' is registered by Pype\Auth\AuthProvider.)
        // $app->stack('admin', ['auth', EnsureAdmin::class]);

        // Map exception types to responses. Unmapped exceptions become a 500.
        $app->renderUsing(PDOException::class, function (PDOException $e) {
            // SQLite/MySQL/Postgres all use SQLSTATE class 23 for constraint violations.
            return str_starts_with((string) $e->getCode(), '23')
                ? Response::error(409, 'Conflicts with existing data')
                : null; // fall through to the default 500
        });

        // Report unexpected errors (default: error_log). Class, Reporter instance, or closure:
        // $app->reportUsing(fn (\Throwable $e, \Pype\Http\Request $request) => $logger->error($e));

        // Services: bind anything your stages type-hint in their constructors.
        // $app->container->singleton(Mailer::class, fn () => new SmtpMailer(env('MAIL_HOST')));
    }
}
