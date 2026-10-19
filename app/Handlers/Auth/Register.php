<?php

declare(strict_types=1);

namespace App\Handlers\Auth;

use Pype\Auth\Auth;
use Pype\Http\Response;
use Pype\Validation\ValidatedInput;

final class Register
{
    public function __invoke(ValidatedInput $input, Auth $auth): Response
    {
        // A taken email never gets here: the route's Validate stage checks unique:users.
        $user = $auth->createUser($input->all());

        return Response::make(['user' => $user, 'token' => $auth->issueToken($user)], 201);
    }
}
