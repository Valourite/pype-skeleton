<?php

declare(strict_types=1);

namespace App\Stages;

use App\Data\User;
use Closure;
use Pype\Database\Connection;
use Pype\Exceptions\HttpException;
use Pype\Http\Request;
use Pype\Pipeline\Provides;
use Pype\Pipeline\Stage;

/**
 * Example loader stage: resolves the {id} route param into a typed User for downstream stages.
 * The database connection is constructor-injected from the container. #[Provides] tells
 * `php bin/pype routes:check` what it puts in the context, so a stage that #[Requires] a User
 * listed before it is caught without running the route.
 */
#[Provides(User::class)]
final class FindUser implements Stage
{
    public function __construct(private readonly Connection $db)
    {
    }

    public function handle(Request $request, Closure $next): mixed
    {
        $id = (int) $request->param('id');

        $user = $this->db->table('users')->where('id', $id)->as(User::class)->first()
            ?? throw HttpException::notFound("User {$id} not found");

        $request->context()->put($user);

        return $next($request);
    }
}
