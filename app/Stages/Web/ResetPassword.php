<?php

declare(strict_types=1);

namespace App\Stages\Web;

use Closure;
use Pype\Auth\PasswordResets;
use Pype\Exceptions\HttpException;
use Pype\Http\Request;
use Pype\Pipeline\Requires;
use Pype\Pipeline\Stage;
use Pype\Validation\ValidatedInput;

/** Set the new password from a reset link, then send the user to sign in with it. */
#[Requires(ValidatedInput::class)]
final class ResetPassword implements Stage
{
    public function __construct(private readonly PasswordResets $resets)
    {
    }

    public function handle(Request $request, Closure $next): mixed
    {
        $input = $request->context()->get(ValidatedInput::class);

        $this->resets->reset($input->get('token'), $input->get('password'))
            ?? throw HttpException::unprocessable(['password' => ['This reset link is invalid or has expired. Ask for a new one.']]);

        return redirect('login', flash: 'Your password has been changed. Sign in with the new one.');
    }
}
