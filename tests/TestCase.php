<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;
use Pype\Application;
use Pype\Database\Migrator;
use Pype\Testing\TestClient;

abstract class TestCase extends BaseTestCase
{
    protected Application $app;
    protected TestClient $client;

    protected function setUp(): void
    {
        // phpunit.xml points DB_DATABASE at :memory:, so every test gets a fresh, migrated database.
        $this->app = require __DIR__ . '/../bootstrap/app.php';
        $this->app->container->get(Migrator::class)->migrate();

        $this->client = new TestClient($this->app);
    }
}
