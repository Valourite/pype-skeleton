<?php

declare(strict_types=1);

namespace App\Stages\Web;

use App\Mail\WelcomeMail;
use Closure;
use Pype\Auth\Auth;
use Pype\Auth\SessionGuard;
use Pype\Http\Request;
use Pype\Mail\Mailer;
use Pype\Pipeline\Requires;
use Pype\Pipeline\Stage;
use Pype\Session\Session;
use Pype\Validation\ValidatedInput;

/** Create the account, sign the browser in, and go to the account page. */
#[Requires(ValidatedInput::class, Session::class)]
final class Register implements Stage
{
    public function __construct(
        private readonly Auth $auth,
        private readonly SessionGuard $guard,
        private readonly Mailer $mailer,
    ) {
    }

    public function handle(Request $request, Closure $next): mixed
    {
        // A taken email never gets here: the route's Validate stage checks unique:users.
        $user = $this->auth->createUser($request->context()->get(ValidatedInput::class)->all());
        $this->mailer->later(new WelcomeMail($user));   // after the response: signing up doesn't wait for the mail server
        $this->guard->login($request->context()->get(Session::class), $user);

        return redirect('account', flash: 'Welcome aboard!');
    }
}
