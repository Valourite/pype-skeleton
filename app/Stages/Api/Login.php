<?php

declare(strict_types=1);

namespace App\Stages\Api;

use Closure;
use Pype\Auth\Auth;
use Pype\Exceptions\HttpException;
use Pype\Http\Request;
use Pype\Pipeline\Requires;
use Pype\Pipeline\Stage;
use Pype\Validation\ValidatedInput;

/** Check the password and answer with a new API token; 401 when it's wrong. */
#[Requires(ValidatedInput::class)]
final class Login implements Stage
{
    public function __construct(private readonly Auth $auth)
    {
    }

    public function handle(Request $request, Closure $next): mixed
    {
        $input = $request->context()->get(ValidatedInput::class);
        $user = $this->auth->attempt($input->get('email'), $input->get('password'))
            ?? throw HttpException::unauthorized('Invalid credentials');

        return ['user' => $user, 'token' => $this->auth->issueToken($user, $input->get('device', 'api'))];
    }
}
