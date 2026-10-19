<?php

declare(strict_types=1);

use App\Handlers\ShowUser;
use App\Handlers\Web\ForgotPassword;
use App\Handlers\Web\Login;
use App\Handlers\Web\Logout;
use App\Handlers\Web\Register;
use App\Handlers\Web\ResendVerification;
use App\Handlers\Web\ResetPassword;
use App\Handlers\Web\VerifyEmail;
use App\Stages\FindUser;
use App\Views\UserProfile;
use Pype\Auth\Authenticate;
use Pype\Auth\PasswordResets;
use Pype\Routing\ValidateSignature;
use Pype\Routing\Router;
use Pype\Stages\Throttle;
use Pype\Validation\Validate;
use Pype\View\View;

/** @var Router $router */

// Pages for browsers. The 'web' stack starts a session (cookie), checks the CSRF token on
// every POST/PUT/PATCH/DELETE, and turns validation errors into "back to the form, with
// errors". An outlet decides how each route's result leaves the pipeline: a View renders HTML.
$router->group('/', ['web'], function (Router $router) {
    // Name routes you link to; templates and handlers then use url('name', [...]).
    $router->get('/', [fn (Router $router) => ['routes' => $router->routes()], new View('welcome')])->name('home');

    // Same stages as GET /users/{id} in routes/api.php — only the outlet differs. UserProfile
    // is a class-based view (app/Views): its own template, data shaping and helper methods.
    $router->get('/users/{id}/profile', [FindUser::class, ShowUser::class, UserProfile::class])->name('users.profile');

    // Guests only see the forms; how you check credentials is up to your handler.
    $router->get('/login', [new View('auth/login')])->name('login');
    $router->get('/register', [new View('auth/register')])->name('register');

    $router->group('/', [new Throttle(maxAttempts: 10, decaySeconds: 60)], function (Router $router) {
        $router->post('/login', [new Validate(['email' => 'required|email', 'password' => 'required|string', 'remember' => 'nullable|accepted']), Login::class]);
        $router->post('/register', [
            new Validate(['name' => 'required|string|max:100', 'email' => 'required|email|unique:users', 'password' => 'required|string|min:8'], messages: ['email.unique' => 'That email is already registered.']),
            Register::class,
        ]);
    });

    // Forgotten password: email a one-time link (Pype\Auth\PasswordResets keeps it safe), then
    // a form for the new password. Throttled so the form can't be used to spam an inbox.
    $router->get('/password/forgot', [new View('auth/forgot')])->name('password.forgot');
    $router->post('/password/forgot', [new Throttle(maxAttempts: 5, decaySeconds: 60), new Validate(['email' => 'required|email']), ForgotPassword::class])->name('password.email');
    $router->get('/password/reset/{token}', [fn (string $token, PasswordResets $resets) => ['token' => $token, 'valid' => $resets->valid($token)], new View('auth/reset')])->name('password.reset');
    $router->post('/password/reset', [
        new Throttle(maxAttempts: 10, decaySeconds: 60),
        new Validate(['token' => 'required|string', 'password' => 'required|string|min:8|confirmed']),
        ResetPassword::class,
    ])->name('password.update');

    // The link from the welcome email. Signed (can't be edited or reused after a day) and tied
    // to the address; works without being signed in.
    $router->get('/email/verify/{id}/{hash}', [new ValidateSignature(), VerifyEmail::class])->name('verification.verify');

    // Signed in only. Browsers without a session are sent to /login and brought back after.
    $router->group('/', [new Authenticate(redirectTo: '/login')], function (Router $router) {
        $router->get('/account', [fn (App\Data\User $user) => ['user' => $user], new View('account')])->name('account');
        $router->post('/logout', [Logout::class])->name('logout');
        $router->post('/email/verification-notification', [new Throttle(maxAttempts: 3, decaySeconds: 60), ResendVerification::class])->name('verification.send');

        // To require a verified email for some pages, add a stage to their group:
        //   new Pype\Auth\EnsureEmailIsVerified(redirectTo: '/account')
    });
});
