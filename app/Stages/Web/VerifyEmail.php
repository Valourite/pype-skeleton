<?php

declare(strict_types=1);

namespace App\Stages\Web;

use Closure;
use Pype\Auth\Auth;
use Pype\Exceptions\HttpException;
use Pype\Http\Request;
use Pype\Pipeline\Stage;

/**
 * The link in the welcome / verification email. ValidateSignature (on the route) has already
 * checked it wasn't changed or expired; the hash ties it to the address it was sent to.
 */
final class VerifyEmail implements Stage
{
    public function __construct(private readonly Auth $auth)
    {
    }

    public function handle(Request $request, Closure $next): mixed
    {
        $user = $this->auth->findUser((int) $request->param('id'));

        if ($user === null || !hash_equals($this->auth->verificationHash($user), (string) $request->param('hash'))) {
            throw HttpException::forbidden('This verification link is not valid for your current email address.');
        }

        $this->auth->markEmailVerified($user);

        return redirect('account', flash: 'Thanks, your email address is verified.');
    }
}
