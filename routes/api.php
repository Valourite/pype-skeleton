<?php

declare(strict_types=1);

use App\Outlets\UserJson;
use App\Stages\Api\Login;
use App\Stages\Api\Logout;
use App\Stages\Api\Register;
use App\Stages\FindUser;
use App\Stages\Health;
use App\Stages\ListUsers;
use Pype\Routing\Router;
use Pype\Stages\Throttle;
use Pype\Validation\Validate;

/** @var Router $router */

// JSON endpoints, served under /api: files named api.php get the prefix, and their route names
// get api. in front (url('api.health')). Clients authenticate with a bearer token from
// /api/auth/login; the browser pages in routes/web.php use a session cookie instead. Same users,
// same stages.

$router->get('/health', [Health::class])->name('health');

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

// Everything in this group requires a bearer token; 'auth' puts the user in the context.
$router->group('/', ['auth'], function (Router $router) {
    // UserJson (app/Outlets) decides what a client sees of a user: here the signed-in one.
    $router->get('/me', [UserJson::class]);

    // The usual routes of one kind of thing: apiResource() names them api.users.index and
    // api.users.show (resource() adds the create/edit form pages too). Leave a key out and that route
    // doesn't exist.
    $router->apiResource('/users', [
        'index' => [ListUsers::class],                       // returns a Page; the default JSON outlet writes it
        // FindUser loads the user into the context; UserJson writes it out. The web profile page
        // (routes/web.php) is the same stage with a page outlet instead.
        'show' => [FindUser::class, UserJson::class],
    ]);
});
