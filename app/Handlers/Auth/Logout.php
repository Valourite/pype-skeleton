<?php

declare(strict_types=1);

namespace App\Handlers\Auth;

use Pype\Auth\AccessToken;
use Pype\Auth\Auth;

/** Revokes the token used for this request. Returns null → 204 No Content. */
final class Logout
{
    public function __invoke(AccessToken $token, Auth $auth): null
    {
        $auth->revoke($token);

        return null;
    }
}
