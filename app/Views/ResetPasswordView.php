<?php

declare(strict_types=1);

namespace App\Views;

use Pype\Auth\PasswordResets;
use Pype\Http\Request;
use Pype\View\ViewOutlet;

/** The "choose a new password" form behind a reset link; says so when the link is no good. */
final class ResetPasswordView extends ViewOutlet
{
    public function __construct(private readonly PasswordResets $resets)
    {
    }

    protected function template(): string
    {
        return 'auth/reset';
    }

    protected function data(mixed $data, Request $request): array
    {
        $token = (string) $request->param('token');

        return ['token' => $token, 'valid' => $this->resets->valid($token)];
    }
}
