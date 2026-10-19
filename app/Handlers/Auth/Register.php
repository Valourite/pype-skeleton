<?php

declare(strict_types=1);

namespace App\Handlers\Auth;

use App\Mail\WelcomeMail;
use Pype\Auth\Auth;
use Pype\Http\Response;
use Pype\Mail\Mailer;
use Pype\Validation\ValidatedInput;

final class Register
{
    public function __invoke(ValidatedInput $input, Auth $auth, Mailer $mailer): Response
    {
        // A taken email never gets here: the route's Validate stage checks unique:users.
        $user = $auth->createUser($input->all());
        $mailer->later(new WelcomeMail($user));   // after the response: signing up doesn't wait for the mail server

        return Response::make(['user' => $user, 'token' => $auth->issueToken($user)], 201);
    }
}
