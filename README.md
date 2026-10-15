# pype — starter app

A lightweight, pipeline-first PHP framework. Every route is its own pipeline: a request goes in
one end, passes through the **stages** you list, and comes out as data — JSON by default, or HTML
through a view.

```
Request ─▶ [global stages] ─▶ route ─▶ [group + route stages] ─▶ Response ─▶ Outlet ─▶ output
```

## Quick start

```bash
composer create-project pype/pype my-app
cd my-app
php bin/pype serve        # http://localhost:8000
```

`create-project` copies this app, installs [`pype/framework`](https://github.com/Valourite/pype-framework)
into `vendor/`, creates `.env`, and runs the migrations.

```bash
php bin/pype routes            # every route and its stages
php bin/pype make:stage Name   # app/Stages/Name.php
php bin/pype make:migration create_posts_table
php bin/pype migrate
composer test
```

## A route

```php
$router->get('/users/{id}', ['auth', FindUser::class, ShowUser::class]);
```

- `'auth'` — a named stack (here: the built-in bearer-token check)
- `FindUser` — a **stage**: `handle(Request $request, Closure $next)`; loads the user into the context
- `ShowUser` — an invokable class: `__invoke(User $user)`, parameters injected by type

Swap the **outlet** — the stage where the flow leaves the pipeline — and the same stages render HTML:

```php
$router->get('/users/{id}/profile', [FindUser::class, ShowUser::class, UserProfile::class]);
```

## Concepts

| Concept    | What it is                                                                                         |
|------------|----------------------------------------------------------------------------------------------------|
| Stage      | a step in a pipeline: class implementing `Stage`, configured instance, closure, or invokable class |
| Injection  | closures/invokables get params by type or name: `fn (User $user, int $id, Connection $db) => ...`  |
| Context    | typed per-request store: `$request->context()->put($user)` / `->get(User::class)`                  |
| Stack      | named reusable stage list: `$app->stack('admin', ['auth', EnsureAdmin::class])`                    |
| Outlet     | the exit stage that writes the result out: `JsonOutlet` (default), `View`, or your own subclass    |
| Provider   | `register(Application $app)` — how the app and composer packages plug in (`config/app.php`)        |
| Errors     | `$app->renderUsing(Type::class, fn ...)` maps exceptions; `$app->reportUsing(...)` logs/alerts     |

Return values are normalised: `Response` as-is, `null` → 204, anything else → `{"data": ...}`.
Exceptions become `{"error": {...}}` *inside* the pipeline, so outer stages still run.

## Built in

| Stage / service                     | Use                                                                     |
|-------------------------------------|-------------------------------------------------------------------------|
| `Pype\Auth\Authenticate` / `'auth'` | bearer-token auth; injects your `User` and the `AccessToken`            |
| `Pype\Auth\Auth`                    | `createUser`, `attempt`, `issueToken`, `revoke`, `revokeAll`            |
| `new Throttle(5, 60)`               | rate limit per IP + route (429 + `Retry-After`)                         |
| `new Validate([...rules])`          | 422 on bad input; `ValidatedInput` injected on success                  |
| `new RequestId()`                   | `X-Request-Id` on every response                                        |
| `new Cors([...origins])`            | CORS headers + preflight                                                |
| `new JsonBody(maxBytes: ...)`       | 413 on big bodies, 400 on malformed JSON                                |

Auth endpoints in `routes/api.php`: `POST /auth/register`, `POST /auth/login`, `POST /auth/logout`, `GET /me`.

## Database

SQLite by default (`database/database.sqlite`, WAL mode for fast concurrent reads). MySQL and
Postgres are configured in `config/database.php`. Type-hint `Pype\Database\Connection` anywhere:

```php
$db->table('users')->where('id', $id)->as(User::class)->first();
$db->table('posts')->insert(['title' => 'Hello']);
$db->select('SELECT * FROM users WHERE email LIKE ?', ['%@example.com']);
$db->transaction(fn (Connection $db) => ...);
```

Migrations are plain `.sql` files in `database/migrations`, applied in filename order.

## Outlets

Every pipeline ends in an outlet. It can sit anywhere in a stage list (pype moves it to the exit),
and the closest one wins: **route → group → global → app default**.

```php
$app->outlet(MyJson::class);                                      // app default (JsonOutlet otherwise)
$router->group('/admin', ['auth', new View('admin/page')], ...);  // every route in the group
$router->get('/report.csv', [LoadReport::class, CsvOutlet::class]); // one route
```

Custom formats extend `Pype\Outlet\Outlet` (`render()` + `contentType()`), or extend `JsonOutlet` and
override `payload()` to change the JSON envelope. `php bin/pype make:outlet CsvOutlet` scaffolds one.

## Views

Plain PHP templates in `resources/views`, styled by `public/css/app.css` (no build step).

- Quick: `new View('welcome')` at the end of a route.
- Class-based: extend `Pype\View\ViewOutlet` (see `app/Views/UserProfile.php`) — choose the template,
  shape the data in `data()`, inject services in the constructor, and call its methods from the
  template as `$view->initials($user)`.

```php
<?php $this->layout('layouts/app', ['title' => $data->name]) ?>
<h1><?= e($data->name) ?></h1>
<?= $this->partial('users/_card', ['user' => $data]) ?>
```

Errors on HTML routes render `errors/{status}.php`, falling back to `errors/error.php`.

## Layout

```
app/          your code: Stages, Handlers, Views, Providers, Data
bootstrap/    builds the Application
config/       plain PHP arrays (app, auth, database)
database/     migrations/*.sql and the SQLite file
public/       web root (index.php, css/)
resources/    views/
routes/       route definitions
tests/        PHPUnit feature tests (Pype\Testing\TestClient, in-memory SQLite)
bin/pype      CLI
vendor/       pype/framework lives here
```
