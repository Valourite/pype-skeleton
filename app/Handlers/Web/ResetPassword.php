<?php

declare(strict_types=1);

namespace App\Handlers\Web;

use Pype\Auth\PasswordResets;
use Pype\Exceptions\HttpException;
use Pype\Http\Response;
use Pype\Session\Session;
use Pype\Validation\ValidatedInput;

/** Set the new password from a reset link, then send the user to sign in with it. */
final class ResetPassword
{
    public function __invoke(ValidatedInput $input, PasswordResets $resets, Session $session): Response
    {
        $resets->reset($input->get('token'), $input->get('password'))
            ?? throw HttpException::unprocessable(['password' => ['This reset link is invalid or has expired. Ask for a new one.']]);

        $session->flash('status', 'Your password has been changed. Sign in with the new one.');

        return Response::redirect(url('login'), 303);
    }
}
