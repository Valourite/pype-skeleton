<?php

declare(strict_types=1);

namespace App\Handlers\Web;

use Pype\Auth\Auth;
use Pype\Exceptions\HttpException;
use Pype\Http\Response;
use Pype\Session\Session;

/**
 * The link in the welcome / verification email. ValidateSignature (on the route) has already
 * checked it wasn't changed or expired; the hash ties it to the address it was sent to.
 */
final class VerifyEmail
{
    public function __invoke(int $id, string $hash, Auth $auth, Session $session): Response
    {
        $user = $auth->findUser($id);

        if ($user === null || !hash_equals($auth->verificationHash($user), $hash)) {
            throw HttpException::forbidden('This verification link is not valid for your current email address.');
        }

        $auth->markEmailVerified($user);
        $session->flash('status', 'Thanks, your email address is verified.');

        return Response::redirect(url('account'), 303);
    }
}
