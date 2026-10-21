<?php

declare(strict_types=1);

namespace App\Stages\Web;

use Closure;
use Pype\Auth\SessionGuard;
use Pype\Http\Request;
use Pype\Pipeline\Requires;
use Pype\Pipeline\Stage;
use Pype\Session\Session;

#[Requires(Session::class)]
final class Logout implements Stage
{
    public function __construct(private readonly SessionGuard $guard)
    {
    }

    public function handle(Request $request, Closure $next): mixed
    {
        // The request too: so this device's "remember me" is forgotten as well.
        $this->guard->logout($request->context()->get(Session::class), $request);

        return redirect('login', flash: 'You have been signed out.');
    }
}
