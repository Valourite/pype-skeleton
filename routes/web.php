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
use App\Views\AccountView;
use App\Views\ResetPasswordView;
use App\Views\UserProfile;
use App\Views\WelcomeView;
use Pype\Auth\Authenticate;
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
    // A page is stages that load what it shows, then a page outlet (app/Views) that hands it to
    // its template. Pages with nothing to load are just the outlet.
    $router->get('/', [WelcomeView::class])->name('home');

    // Same stages as GET /api/users/{id} in routes/api.php — only the outlet differs. UserProfile
    // is a class-based view (app/Views): its own template, data shaping and helper methods.
    $router->get('/users/{id}/profile', [FindUser::class, ShowUser::class, UserProfile::class])->name('users.profile');

    // Guests only see the forms; how you check credentials is up to your handler.
    $router->get('/login', [View::with('auth/login')])->name('login');
    $router->get('/register', [View::with('auth/register')])->name('register');

    $router->group('/', [Throttle::with(maxAttempts: 10, decaySeconds: 60)], function (Router $router) {
        $router->post('/login', [Validate::with(['email' => 'required|email', 'password' => 'required|string', 'remember' => 'nullable|accepted']), Login::class]);
        $router->post('/register', [
            Validate::with(['name' => 'required|string|max:100', 'email' => 'required|email|unique:users', 'password' => 'required|string|min:8'], messages: ['email.unique' => 'That email is already registered.']),
            Register::class,
        ]);
    });

    // Forgotten password: email a one-time link (Pype\Auth\PasswordResets keeps it safe), then
    // a form for the new password. Throttled so the form can't be used to spam an inbox.
    $router->get('/password/forgot', [View::with('auth/forgot')])->name('password.forgot');
    $router->post('/password/forgot', [Throttle::with(maxAttempts: 5, decaySeconds: 60), Validate::with(['email' => 'required|email']), ForgotPassword::class])->name('password.email');
    $router->get('/password/reset/{token}', [ResetPasswordView::class])->name('password.reset');
    $router->post('/password/reset', [
        Throttle::with(maxAttempts: 10, decaySeconds: 60),
        Validate::with(['token' => 'required|string', 'password' => 'required|string|min:8|confirmed']),
        ResetPassword::class,
    ])->name('password.update');

    // The link from the welcome email. Signed (can't be edited or reused after a day) and tied
    // to the address; works without being signed in.
    $router->get('/email/verify/{id}/{hash}', [ValidateSignature::class, VerifyEmail::class])->name('verification.verify');

    // Signed in only. Browsers without a session are sent to /login and brought back after.
    $router->group('/', [Authenticate::with(redirectTo: '/login')], function (Router $router) {
        $router->get('/account', [AccountView::class])->name('account');   // the group's Authenticate put the User in the context
        $router->post('/logout', [Logout::class])->name('logout');
        // Per signed-in user, not per IP: three resends a minute, whichever device asks.
        $router->post('/email/verification-notification', [Throttle::with(maxAttempts: 3, decaySeconds: 60, by: App\Data\User::class), ResendVerification::class])->name('verification.send');

        // To require a verified email for some pages, add a stage to their group:
        //   Pype\Auth\EnsureEmailIsVerified::with(redirectTo: '/account')
    });
});
