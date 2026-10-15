<?php

declare(strict_types=1);

namespace App\Handlers\Web;

use Pype\Auth\Auth;
use Pype\Auth\SessionGuard;
use Pype\Database\Connection;
use Pype\Exceptions\HttpException;
use Pype\Http\Response;
use Pype\Session\Session;
use Pype\Validation\ValidatedInput;

final class Register
{
    public function __invoke(ValidatedInput $input, Auth $auth, Connection $db, SessionGuard $guard, Session $session): Response
    {
        if ($db->table('users')->where('email', $input->get('email'))->exists()) {
            throw HttpException::unprocessable(['email' => ['That email is already registered.']]);
        }

        $guard->login($session, $auth->createUser($input->all()));
        $session->flash('status', 'Welcome aboard!');

        return Response::redirect('/account', 303);
    }
}
