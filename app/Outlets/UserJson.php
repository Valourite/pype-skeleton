<?php

declare(strict_types=1);

namespace App\Outlets;

use App\Data\User;
use Pype\Http\Request;
use Pype\Outlet\JsonOutlet;
use Pype\Pipeline\Requires;

/**
 * Example JSON outlet: what an API client sees of a user, wherever the user came from (FindUser,
 * or the signed-in user on /api/me). The same idea as the page outlets in app/Views, for JSON:
 * stages load, the outlet decides what goes out.
 */
#[Requires(User::class)]
final class UserJson extends JsonOutlet
{
    protected function data(mixed $data, Request $request): mixed
    {
        $user = $request->context()->get(User::class);

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'verified' => $user->email_verified_at !== null,
            'member_since' => $user->created_at,
        ];
    }
}
