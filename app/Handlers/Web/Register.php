<?php

declare(strict_types=1);

namespace App\Handlers\Web;

use Pype\Auth\Auth;
use Pype\Auth\SessionGuard;
use Pype\Http\Response;
use Pype\Session\Session;
use Pype\Validation\ValidatedInput;

final class Register
{
    public function __invoke(ValidatedInput $input, Auth $auth, SessionGuard $guard, Session $session): Response
    {
        // A taken email never gets here: the route's Validate stage checks unique:users.
        $guard->login($session, $auth->createUser($input->all()));
        $session->flash('status', 'Welcome aboard!');

        return Response::redirect(url('account'), 303);
    }
}
