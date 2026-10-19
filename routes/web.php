<?php

declare(strict_types=1);

use App\Handlers\ShowUser;
use App\Handlers\Web\Login;
use App\Handlers\Web\Logout;
use App\Handlers\Web\Register;
use App\Stages\FindUser;
use App\Views\UserProfile;
use Pype\Auth\Authenticate;
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
        $router->post('/login', [new Validate(['email' => 'required|email', 'password' => 'required|string']), Login::class]);
        $router->post('/register', [
            new Validate(['name' => 'required|string|max:100', 'email' => 'required|email', 'password' => 'required|string|min:8']),
            Register::class,
        ]);
    });

    // Signed in only. Browsers without a session are sent to /login and brought back after.
    $router->group('/', [new Authenticate(redirectTo: '/login')], function (Router $router) {
        $router->get('/account', [fn (App\Data\User $user) => ['user' => $user], new View('account')])->name('account');
        $router->post('/logout', [Logout::class])->name('logout');
    });
});
