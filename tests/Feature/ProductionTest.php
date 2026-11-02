<?php

declare(strict_types=1);

namespace Tests\Feature;

use Pype\Application;
use Pype\Testing\TestClient;
use RuntimeException;
use Tests\TestCase;

/**
 * This app booted as on a server: APP_ENV=production, even with APP_DEBUG=true left in .env. The
 * log viewer and dev mailbox don't exist, and errors say nothing about what went wrong.
 */
final class ProductionTest extends TestCase
{
    private const VARS = ['APP_ENV' => 'production', 'APP_DEBUG' => 'true', 'LOG_VIEWER' => 'true', 'MAIL_MAILBOX' => 'true', 'MAIL_DRIVER' => 'log'];

    private function production(): Application
    {
        $saved = [];
        foreach (self::VARS as $key => $value) {
            $saved[$key] = getenv($key);
            putenv("{$key}={$value}");
            $_ENV[$key] = $value;
        }

        try {
            return require __DIR__ . '/../../bootstrap/app.php';
        } finally {
            foreach ($saved as $key => $value) {
                $value === false ? putenv($key) : putenv("{$key}={$value}");
                if ($value === false) {
                    unset($_ENV[$key]);
                } else {
                    $_ENV[$key] = $value;
                }
            }
        }
    }

    public function test_dev_pages_do_not_exist_and_errors_hide_the_details(): void
    {
        $app = $this->production();
        $this->assertTrue($app->isProduction());
        $this->assertFalse($app->config('app.debug'));

        $app->router->get('/boom', [fn () => throw new RuntimeException('db password is hunter2')]);
        $client = new TestClient($app);

        foreach (['/_pype/logs', '/_pype/logs/entries', '/_pype/mail'] as $path) {
            $client->get($path, ['Accept' => 'text/html'])->assertStatus(404);
        }

        foreach (['text/html', 'application/json'] as $accept) {
            $res = $client->get('/boom', ['Accept' => $accept])->assertStatus(500);
            $this->assertStringNotContainsString('hunter2', $res->body());
            $this->assertStringNotContainsString('.php', $res->body());
        }
    }
}
