<?php

declare(strict_types=1);

namespace App\Handlers\Web;

use Pype\Auth\SessionGuard;
use Pype\Http\Response;
use Pype\Session\Session;

final class Logout
{
    public function __invoke(SessionGuard $guard, Session $session): Response
    {
        $guard->logout($session);
        $session->flash('status', 'You have been signed out.');

        return Response::redirect('/login', 303);
    }
}
