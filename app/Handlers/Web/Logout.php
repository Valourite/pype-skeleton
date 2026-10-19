<?php

declare(strict_types=1);

namespace App\Handlers\Web;

use Pype\Auth\SessionGuard;
use Pype\Http\Request;
use Pype\Http\Response;
use Pype\Session\Session;

final class Logout
{
    public function __invoke(SessionGuard $guard, Session $session, Request $request): Response
    {
        $guard->logout($session, $request);   // the request: so this device's "remember me" is forgotten too
        $session->flash('status', 'You have been signed out.');

        return Response::redirect(url('login'), 303);
    }
}
