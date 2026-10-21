<?php

declare(strict_types=1);

namespace App\Views;

use App\Data\User;
use Pype\Http\Request;
use Pype\View\ViewOutlet;

/**
 * Example class-based view: an outlet with its own template, data shaping and helpers.
 * The template receives $user and $memberSince, and can call $view->initials().
 */
final class UserProfile extends ViewOutlet
{
    protected function template(): string
    {
        return 'users/profile';
    }

    /** FindUser (earlier in the route) put the User in the context. */
    protected function data(mixed $data, Request $request): array
    {
        $user = $request->context()->get(User::class);

        return [
            'user' => $user,
            'memberSince' => $user->created_at === null ? null : date('j F Y', strtotime($user->created_at)),
        ];
    }

    public function initials(User $user): string
    {
        $parts = array_slice(preg_split('/\s+/', trim($user->name)) ?: [], 0, 2);

        return implode('', array_map(static fn (string $p) => mb_strtoupper(mb_substr($p, 0, 1)), $parts));
    }
}
