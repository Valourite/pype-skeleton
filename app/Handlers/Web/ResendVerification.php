<?php

declare(strict_types=1);

namespace App\Handlers\Web;

use App\Data\User;
use App\Mail\VerifyEmailMail;
use Pype\Auth\Auth;
use Pype\Http\Response;
use Pype\Mail\Mailer;
use Pype\Session\Session;

final class ResendVerification
{
    public function __invoke(User $user, Auth $auth, Mailer $mailer, Session $session): Response
    {
        if (!$auth->isEmailVerified($user)) {
            $mailer->later(new VerifyEmailMail($user));
        }

        $session->flash('status', "We've sent a new verification link to {$user->email}.");

        return Response::redirect(url('account'), 303);
    }
}
