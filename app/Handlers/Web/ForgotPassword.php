<?php

declare(strict_types=1);

namespace App\Handlers\Web;

use App\Mail\ResetPasswordMail;
use Pype\Auth\Auth;
use Pype\Auth\PasswordResets;
use Pype\Http\Response;
use Pype\Mail\Mailer;
use Pype\Session\Session;
use Pype\Validation\ValidatedInput;

/**
 * "Forgot password": email a reset link. The answer is the same whether or not the address
 * has an account, so the form can't be used to find out who is registered.
 */
final class ForgotPassword
{
    public function __invoke(ValidatedInput $input, Auth $auth, PasswordResets $resets, Mailer $mailer, Session $session): Response
    {
        $user = $auth->findBy($input->get('email'));
        $token = $user === null ? null : $resets->create($user);   // null too if a link went out moments ago

        if ($token !== null) {
            $mailer->later(new ResetPasswordMail($user, $token));
        }

        $session->flash('status', 'If that email has an account, a link to reset the password is on its way.');

        return Response::redirect(url('password.forgot'), 303);
    }
}
