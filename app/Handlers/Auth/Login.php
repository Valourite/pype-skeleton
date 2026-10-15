<?php

declare(strict_types=1);

namespace App\Handlers\Auth;

use Pype\Auth\Auth;
use Pype\Exceptions\HttpException;
use Pype\Validation\ValidatedInput;

final class Login
{
    public function __invoke(ValidatedInput $input, Auth $auth): array
    {
        $user = $auth->attempt($input->get('email'), $input->get('password'))
            ?? throw HttpException::unauthorized('Invalid credentials');

        return ['user' => $user, 'token' => $auth->issueToken($user, $input->get('device', 'api'))];
    }
}
