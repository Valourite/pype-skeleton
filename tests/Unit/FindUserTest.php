<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Data\User;
use App\Stages\FindUser;
use Database\Factories\UserFactory;
use Pype\Database\Connection;
use Pype\Exceptions\HttpException;
use Pype\Testing\Chain;
use Tests\TestCase;

/**
 * One stage on its own, no HTTP: Chain runs it with route params (and, for later stages,
 * whatever earlier ones would have put in the context via ->with(...)), then the test looks at
 * what it left in the context.
 */
final class FindUserTest extends TestCase
{
    public function test_it_puts_the_user_in_the_context(): void
    {
        $ada = UserFactory::new($this->app->container->get(Connection::class))->create(['name' => 'Ada']);

        $result = Chain::of([FindUser::class], $this->app)->params(['id' => $ada->id])->run();

        $this->assertTrue($result->reachedEnd());
        $this->assertSame('Ada', $result->context()->get(User::class)->name);
    }

    public function test_an_unknown_id_is_a_404(): void
    {
        $this->expectException(HttpException::class);

        Chain::of([FindUser::class], $this->app)->params(['id' => 999])->run();
    }
}
