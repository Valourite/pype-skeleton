<?php

declare(strict_types=1);

use App\Data\User;
use App\Handlers\Auth\Login;
use App\Handlers\Auth\Logout;
use App\Handlers\Auth\Register;
use App\Handlers\ShowUser;
use App\Stages\FindUser;
use Pype\Database\Connection;
use Pype\Routing\Router;
use Pype\Stages\Throttle;
use Pype\Validation\Validate;

/** @var Router $router */

// JSON endpoints. Clients authenticate with a bearer token from /auth/login; the browser
// pages in routes/web.php use a session cookie instead. Same users, same stages.

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
