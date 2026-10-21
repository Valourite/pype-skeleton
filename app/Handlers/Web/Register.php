<?php

declare(strict_types=1);

namespace App\Handlers\Web;

use App\Mail\WelcomeMail;
use Pype\Auth\Auth;
use Pype\Auth\SessionGuard;
use Pype\Http\Response;
use Pype\Mail\Mailer;
use Pype\Session\Session;
use Pype\Validation\ValidatedInput;

final class Register
{
    public function __invoke(ValidatedInput $input, Auth $auth, SessionGuard $guard, Session $session, Mailer $mailer): Response
    {
        // A taken email never gets here: the route's Validate stage checks unique:users.
        $user = $auth->createUser($input->all());
        $mailer->later(new WelcomeMail($user));   // after the response: signing up doesn't wait for the mail server
        $guard->login($session, $user);

        return redirect('account', flash: 'Welcome aboard!');
    }
}
