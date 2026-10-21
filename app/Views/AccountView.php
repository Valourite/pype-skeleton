<?php

declare(strict_types=1);

namespace App\Views;

use App\Data\User;
use Pype\Http\Request;
use Pype\Pipeline\Requires;
use Pype\View\ViewOutlet;

/** The signed-in user's page. Authenticate (earlier in the route) put the User in the context. */
#[Requires(User::class)]
final class AccountView extends ViewOutlet
{
    protected function template(): string
    {
        return 'account';
    }

    protected function data(mixed $data, Request $request): array
    {
        return ['user' => $request->context()->get(User::class)];
    }
}
