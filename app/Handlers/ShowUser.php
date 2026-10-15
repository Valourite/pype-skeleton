<?php

declare(strict_types=1);

namespace App\Handlers;

use App\Data\User;

/**
 * Example invokable stage: no Stage interface, no $next — parameters are injected.
 * User comes from the request context (put there by FindUser).
 */
final class ShowUser
{
    public function __invoke(User $user): User
    {
        return $user;
    }
}
