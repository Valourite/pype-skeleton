<?php

declare(strict_types=1);

use App\Data\User;
use App\Handlers\Auth\Login;
use App\Handlers\Auth\Logout;
use App\Handlers\Auth\Register;
use App\Handlers\ShowUser;
use App\Stages\FindUser;
use Pype\Database\Connection;
use Pype\Http\Request;
use Pype\Routing\Router;
use Pype\Stages\Throttle;
use Pype\Validation\Validate;

/** @var Router $router */

// JSON endpoints, served under /api: files named api.php get the prefix, and their route names
// get api. in front (url('api.health')). Clients authenticate with a bearer token from
// /api/auth/login; the browser pages in routes/web.php use a session cookie instead. Same users,
// same stages.

$router->get('/health', fn () => ['status' => 'ok'])->name('health');

$router->group('/auth', [Throttle::with(maxAttempts: 10, decaySeconds: 60)], function (Router $router) {
    $router->post('/register', [
        Validate::with(['name' => 'required|string|max:100', 'email' => 'required|email|unique:users', 'password' => 'required|string|min:8'], messages: ['email.unique' => 'That email is already registered.']),
        Register::class,
    ]);

    $router->post('/login', [
        Validate::with(['email' => 'required|email', 'password' => 'required|string']),
        Login::class,
    ]);

    $router->post('/logout', ['auth', Logout::class]);
});

// Everything in this group requires a bearer token; the user is injected by type.
$router->group('/', ['auth'], function (Router $router) {
    $router->get('/me', fn (User $user) => $user);

    // The usual routes of one kind of thing: apiResource() names them api.users.index and
    // api.users.show (resource() adds the create/edit form pages too). Leave a key out and that route
    // doesn't exist.
    $router->apiResource('/users', [
        // Paginated: ?page=2 → {"data": [...], "meta": {"pagination": {page, per_page, total, last_page}}}
        'index' => fn (Connection $db, Request $request) => $db->table('users')->orderBy('id')->as(User::class)->paginate(25, $request),
        // A route is its own pipeline: FindUser loads the user into the context, ShowUser returns it.
        'show' => [FindUser::class, ShowUser::class],
    ]);
});
