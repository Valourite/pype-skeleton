<?php

declare(strict_types=1);

namespace App\Stages\Web;

use App\Data\User;
use App\Mail\VerifyEmailMail;
use Closure;
use Pype\Auth\Auth;
use Pype\Http\Request;
use Pype\Mail\Mailer;
use Pype\Pipeline\Requires;
use Pype\Pipeline\Stage;

#[Requires(User::class)]
final class ResendVerification implements Stage
{
    public function __construct(private readonly Auth $auth, private readonly Mailer $mailer)
    {
    }

    public function handle(Request $request, Closure $next): mixed
    {
        $user = $request->context()->get(User::class);

        if (!$this->auth->isEmailVerified($user)) {
            $this->mailer->later(new VerifyEmailMail($user));
        }

        return redirect('account', flash: "We've sent a new verification link to {$user->email}.");
    }
}
