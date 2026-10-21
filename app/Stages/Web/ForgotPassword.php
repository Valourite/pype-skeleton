<?php

declare(strict_types=1);

namespace App\Stages\Web;

use App\Mail\ResetPasswordMail;
use Closure;
use Pype\Auth\Auth;
use Pype\Auth\PasswordResets;
use Pype\Http\Request;
use Pype\Mail\Mailer;
use Pype\Pipeline\Requires;
use Pype\Pipeline\Stage;
use Pype\Validation\ValidatedInput;

/**
 * "Forgot password": email a reset link. The answer is the same whether or not the address
 * has an account, so the form can't be used to find out who is registered.
 */
#[Requires(ValidatedInput::class)]
final class ForgotPassword implements Stage
{
    public function __construct(
        private readonly Auth $auth,
        private readonly PasswordResets $resets,
        private readonly Mailer $mailer,
    ) {
    }

    public function handle(Request $request, Closure $next): mixed
    {
        $user = $this->auth->findBy($request->context()->get(ValidatedInput::class)->get('email'));
        $token = $user === null ? null : $this->resets->create($user);   // null too if a link went out moments ago

        if ($token !== null) {
            $this->mailer->later(new ResetPasswordMail($user, $token));
        }

        return redirect('password.forgot', flash: 'If that email has an account, a link to reset the password is on its way.');
    }
}
