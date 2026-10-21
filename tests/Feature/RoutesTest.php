<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

/** Every route lists its stages in an order that works: see `php bin/pype routes:check`. */
final class RoutesTest extends TestCase
{
    public function test_every_stage_comes_after_what_it_needs(): void
    {
        $this->assertSame([], $this->app->checkRoutes());
    }
}
