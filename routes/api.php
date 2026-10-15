<?php

declare(strict_types=1);

use App\Data\User;
use App\Handlers\Auth\Login;
use App\Handlers\Auth\Logout;
use App\Handlers\Auth\Register;
use App\Handlers\ShowUser;
use App\Stages\FindUser;
use App\Views\UserProfile;
use Pype\Database\Connection;
use Pype\Routing\Router;
use Pype\Stages\Throttle;
use Pype\Validation\Validate;
use Pype\View\View;

/** @var Router $router */

// ── Pages ─────────────────────────────────────────────────────────────────────
// An outlet decides how a route's result leaves the pipeline. JSON is the default;
// a View renders a template instead.
$router->get('/', [fn (Router $router) => ['routes' => $router->routes()], new View('welcome')]);

// Same stages as GET /users/{id} below — only the outlet differs. UserProfile is a
// class-based view (app/Views): its own template, data shaping and helper methods.
$router->get('/users/{id}/profile', [FindUser::class, ShowUser::class, UserProfile::class]);

// ── API ───────────────────────────────────────────────────────────────────────
$router->get('/health', fn () => ['status' => 'ok']);

$router->group('/auth', [new Throttle(maxAttempts: 10, decaySeconds: 60)], function (Router $router) {
    $router->post('/register', [
        new Validate(['name' => 'required|string|max:100', 'email' => 'required|email', 'password' => 'required|string|min:8']),
        Register::class,
    ]);

    $router->post('/login', [
        new Validate(['email' => 'required|email', 'password' => 'required|string']),
        Login::class,
    ]);

    $router->post('/logout', ['auth', Logout::class]);
});

// Everything in this group requires a bearer token; the user is injected by type.
$router->group('/', ['auth'], function (Router $router) {
    $router->get('/me', fn (User $user) => $user);

    $router->get('/users', fn (Connection $db) => $db->table('users')->orderBy('id')->as(User::class)->get());

    // A route is its own pipeline: FindUser loads the user into the context, ShowUser returns it.
    $router->get('/users/{id}', [FindUser::class, ShowUser::class]);
});
