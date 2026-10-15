<?php

declare(strict_types=1);

namespace App\Handlers\Web;

use Pype\Auth\Auth;
use Pype\Auth\SessionGuard;
use Pype\Exceptions\HttpException;
use Pype\Http\Response;
use Pype\Session\Session;
use Pype\Validation\ValidatedInput;

/**
 * Browser login: check the password, then remember the user in the session. Wrong
 * credentials are a validation error, so the 'web' stack sends the user back to the form.
 */
final class Login
{
    public function __invoke(ValidatedInput $input, Auth $auth, SessionGuard $guard, Session $session): Response
    {
        $user = $auth->attempt($input->get('email'), $input->get('password'))
            ?? throw HttpException::unprocessable(['email' => ['These credentials do not match our records.']]);

        $guard->login($session, $user);

        return Response::redirect($guard->intended($session, '/account'), 303);
    }
}
