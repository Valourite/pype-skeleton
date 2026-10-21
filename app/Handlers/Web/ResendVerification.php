<?php

declare(strict_types=1);

namespace App\Handlers\Web;

use App\Data\User;
use App\Mail\VerifyEmailMail;
use Pype\Auth\Auth;
use Pype\Http\Response;
use Pype\Mail\Mailer;

final class ResendVerification
{
    public function __invoke(User $user, Auth $auth, Mailer $mailer): Response
    {
        if (!$auth->isEmailVerified($user)) {
            $mailer->later(new VerifyEmailMail($user));
        }

        return redirect('account', flash: "We've sent a new verification link to {$user->email}.");
    }
}
