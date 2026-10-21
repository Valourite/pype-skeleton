<?php

declare(strict_types=1);

namespace App\Stages\Api;

use Closure;
use Pype\Auth\AccessToken;
use Pype\Auth\Auth;
use Pype\Http\Request;
use Pype\Pipeline\Requires;
use Pype\Pipeline\Stage;

/** Revoke the token this request came with (the 'auth' stack put it in the context). Nothing back: 204. */
#[Requires(AccessToken::class)]
final class Logout implements Stage
{
    public function __construct(private readonly Auth $auth)
    {
    }

    public function handle(Request $request, Closure $next): mixed
    {
        $this->auth->revoke($request->context()->get(AccessToken::class));

        return null;
    }
}
