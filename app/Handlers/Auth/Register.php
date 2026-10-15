<?php

declare(strict_types=1);

namespace App\Handlers\Auth;

use Pype\Auth\Auth;
use Pype\Database\Connection;
use Pype\Exceptions\HttpException;
use Pype\Http\Response;
use Pype\Validation\ValidatedInput;

final class Register
{
    public function __invoke(ValidatedInput $input, Auth $auth, Connection $db): Response
    {
        if ($db->table('users')->where('email', $input->get('email'))->exists()) {
            throw HttpException::unprocessable(['email' => ['That email is already registered.']]);
        }

        $user = $auth->createUser($input->all());

        return Response::make(['user' => $user, 'token' => $auth->issueToken($user)], 201);
    }
}
