<?php

declare(strict_types=1);

namespace App\Stages\Web;

use Closure;
use Pype\Auth\Auth;
use Pype\Auth\SessionGuard;
use Pype\Exceptions\HttpException;
use Pype\Http\Request;
use Pype\Pipeline\Requires;
use Pype\Pipeline\Stage;
use Pype\Session\Session;
use Pype\Validation\ValidatedInput;

/**
 * Browser login: check the password, then remember the user in the session. Wrong
 * credentials are a validation error, so the 'web' stack sends the user back to the form.
 */
#[Requires(ValidatedInput::class, Session::class)]
final class Login implements Stage
{
    public function __construct(private readonly Auth $auth, private readonly SessionGuard $guard)
    {
    }

    public function handle(Request $request, Closure $next): mixed
    {
        $input = $request->context()->get(ValidatedInput::class);
        $session = $request->context()->get(Session::class);

        $user = $this->auth->attempt($input->get('email'), $input->get('password'))
            ?? throw HttpException::unprocessable(['email' => ['These credentials do not match our records.']]);

        $this->guard->login($session, $user, remember: (bool) $input->get('remember'), userAgent: $request->header('user-agent'));

        return redirect($this->guard->intended($session, url('account')));
    }
}
