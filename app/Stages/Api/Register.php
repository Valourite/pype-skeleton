<?php

declare(strict_types=1);

namespace App\Stages\Api;

use App\Mail\WelcomeMail;
use Closure;
use Pype\Auth\Auth;
use Pype\Http\Request;
use Pype\Http\Response;
use Pype\Mail\Mailer;
use Pype\Pipeline\Requires;
use Pype\Pipeline\Stage;
use Pype\Validation\ValidatedInput;

/** Create the account and answer with the user and an API token (201). The last stage of its route. */
#[Requires(ValidatedInput::class)]
final class Register implements Stage
{
    public function __construct(private readonly Auth $auth, private readonly Mailer $mailer)
    {
    }

    public function handle(Request $request, Closure $next): mixed
    {
        // A taken email never gets here: the route's Validate stage checks unique:users.
        $user = $this->auth->createUser($request->context()->get(ValidatedInput::class)->all());
        $this->mailer->later(new WelcomeMail($user));   // after the response: signing up doesn't wait for the mail server

        return Response::make(['user' => $user, 'token' => $this->auth->issueToken($user)], 201);
    }
}
