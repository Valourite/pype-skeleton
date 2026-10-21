# Notes for working on a pype app

This app runs on **pype** (`pype/framework`): each route is a pipeline of **stages**, and an
**outlet** at the end writes the result (JSON by default, HTML via views). The guide to everything pype
offers, with examples, is `vendor/pype/framework/docs/GUIDE.md`: read the relevant section before
building something. Framework internals and design rules are in the framework repo
(`vendor/pype/framework/CLAUDE.md` and `vendor/pype/framework/docs/`).

## How to build features here

- **Routes:**
  - `routes/web.php` is for browser pages: wrap them in `['web']` for sessions, CSRF and
    validation errors back to the form;
  - `routes/api.php` is for JSON under `/api`: use `['auth']` for bearer tokens.
  - Every `.php` file in `routes/` loads (`$app->routesIn()`), subfolders too. Split by feature with
    `php bin/pype make:routes quotes` (writes `routes/quotes/web.php` and `routes/quotes/api.php`). Files
    named `api.php` get the `/api` prefix and `api.` route names; files and folders starting with `_` are skipped.
  - The usual routes of one kind of thing: `$router->resource('/items', ['index' => [...], 'create' => ...,
    'store' => ..., 'show' => ..., 'edit' => ..., 'update' => ..., 'destroy' => ...])` (names `items.index`...);
    `apiResource()` without the form pages. Only the keys you give become routes.
- **Do one thing per stage**, and reuse stages across routes: `Validate` → load something →
  authorize → handler. Stages live in `app/Stages/` (`php bin/pype make:stage Name`).
- **Stages in a route:** `LoadThing::class` when it needs no arguments; `Throttle::with(5, 60)`,
  `Validate::with([...])`, `View::with('page')` (or `stage(MyStage::class, kind: 'x')`) when it does.
  Arguments are plain data (scalars, arrays, enums); the constructor's other parameters are services,
  autowired. These are built only when a request reaches them, fresh each time. `new X(...)` still
  works but is built while the routes file loads and shared by every request a Swoole worker serves.
  Give your own stages `::with()` with `use Pype\Pipeline\WithArguments;`.
- **Declare what a stage reads and puts:** `#[Requires(User::class)]`, `#[Provides(Quote::class)]` on the
  class (or `implements DeclaresContext` with static `requires()`/`provides()`). `php bin/pype routes:check`
  then reports any route that lists a stage before the one that provides what it needs, and
  `tests/Feature/RoutesTest.php` runs the same check in `composer test`. Undeclared stages are skipped.
- **Handlers** are invokable classes or closures at the end of the route. Their parameters are
  injected by type or route-param name: `fn (User $user, int $id, Connection $db) => ...`.
  Return data, and let the outlet format it.
- **Hand data between stages via the request context:** `$request->context()->put($thing)`,
  then a later stage or handler takes it as a typed parameter. Never put per-request data in a
  service, singleton or static: the app may run under OpenSwoole, where one process serves many
  requests.
- **Pages:**
  - `View::with('template')` as the route's outlet; templates are plain PHP in
    `resources/views` (`e()` to escape). The extension picks the engine, so `quotes/show.latte`
    (`composer require latte/latte`) or `quotes/show.blade.php` (`composer require eftec/bladeone`) work
    too, mixed freely; names stay extension-free (`View::with('quotes/show')`). Every page template, layout and partial gets `$csrf` (the token),
    `$old('field')` (what was typed before a failed submit; `'lines.3.qty'` for nested), `$error('field')`
    (first error or null) and `$errors`. Don't assign to `$error`/`$old` (`$message = $error('x')`);
  - most pages: stages load what the page shows into the context, then a page outlet in `app/Views`
    (`php bin/pype make:view QuoteView`, extends `Pype\View\ViewOutlet`) reads it in `data()` and picks
    the template: `[LoadQuote::class, QuoteView::class]`. See `WelcomeView`, `AccountView`, `ResetPasswordView`.
- **Forms:**
  - POST with `<input type="hidden" name="_token" value="<?= e($csrf) ?>">`;
  - PUT/PATCH/DELETE via `<?= $this->method('PATCH') ?>`;
  - after a form, return `redirect('route.name', ['id' => 3], flash: 'Saved.')` from the handler (or
    any stage, to stop there), end the route with `RedirectOutlet::with('route.name', flash: 'Saved.')`,
    or reuse an API handler with `RedirectOnSuccess::with('route.name', 'Saved.')`. Route names fill
    `{id}` from the current route; the flash is shown on the next page.
- **Database:**
  - type-hint `Pype\Database\Connection`;
  - use `$db->table(...)` (query builder) and plain data classes (`->as(Thing::class)`). There
    is no ORM by design;
  - migrations: `php bin/pype make:migration create_things_table` writes a schema-builder
    migration that runs on SQLite, MySQL and Postgres, with a `down()` that undoes it
    (`migrate:rollback`). Once a migration ran in production, change things with a new one.
    `foreignId('x_id')->constrained()` also indexes the column on SQLite/Postgres (`->withoutIndex()`
    to skip);
  - lists: `->paginate(25, $request)` (page from `?page=`, clamped; JSON gets `meta.pagination`, a view
    gets `$page` with `links()`, `previousUrl()`, `nextUrl()` that keep the other query parameters);
    `simplePaginate()` skips the `COUNT(*)` on big tables.
  - test and demo data: factories in `database/factories/` (`php bin/pype make:factory Thing`;
    `ThingFactory::new($db)->create()`, `->count(10)->create()`, `->insert()` for volume) and
    seeders in `database/seeders/` (`php bin/pype db:seed`, `migrate:fresh --seed`). Tests that
    only need a user to exist use `UserFactory` instead of registering over HTTP.
- **Background work:** slow or retryable work goes in a job (`php bin/pype make:job Name`,
  `app/Jobs/`), pushed with `Pype\Queue\Queue`; mail with `$mailer->queue(...)`. Recurring
  tasks go in `routes/schedule.php`.
- **Prefer native PHP** (`password_hash`, `filter_var`, `DateTimeImmutable`, ...) over pulling in
  packages. pype aims to stay fast and feel like PHP.

## Built-ins: check here before writing your own

Each has a section in `vendor/pype/framework/docs/GUIDE.md` with examples. Writing your own
version of one of these is almost always a mistake.

| Need | Use |
|---|---|
| validate input (incl. `unique:`/`exists:`, dates, arrays, conditional) | `Validate::with([...])` / a `Validate` subclass; `status:` for 400; `TrimStrings` already runs globally. `min`/`max` inclusive. Per user/company: `unique:items,sku,{id},id,company_id={user.company_id}` (also `exists:`), or `rulesFor(Request)` in a subclass; keep a unique index too. Forms with rows: drop empty rows in `prepare(array $input)` with `array_filter` (keep the keys, so errors name the right row) |
| who the user is | `'auth'` stack (session or API token); `Pype\Auth\JwtGuard` for JWTs; `Authenticate(redirectTo:)` for pages |
| **records that belong to a user** | load them in a stage and give **404 (not 403) when it isn't theirs**, so ids don't leak; put the record in the context for the handler (GUIDE "Roles, ownership..."). No roles system: admin = a column + a stage |
| rate limiting | `Throttle::with(maxAttempts, decaySeconds)`: per IP + route; `by: User::class` per signed-in user, `key: 'send'` to share a counter between routes |
| signed links, named routes | `url('name', [...])`, `signed_url(...)` + `ValidateSignature` |
| redirect after a form / from a stage | `redirect('name', [...], flash: '...')`, `RedirectOutlet::with('name', flash: '...')` |
| pagination | `->paginate(25, $request)` + `$page->links()` in the view; `simplePaginate()` without a count |
| cache | `Pype\Cache\Cache`: `remember`, locks, `stale()`; per-user data in `group("user.{$id}")`, **flush the group after every write** to that user's data |
| mail | `Mailer::send()`, `later()` (after the response), `queue()` (worker, retries: only off the request with `QUEUE_DRIVER=database` + `queue:work`; the default `sync` sends at once). `MAIL_DRIVER=log` delivers nothing: while developing, read what was sent (and click its links) at `/_pype/mail` |
| background jobs, cron | `Pype\Queue\Queue` (`make:job`), `routes/schedule.php` |
| encrypt a value | `Pype\Security\Encrypter` |
| inserts | `insert()` (id), `insertReturning([...], ['id', 'created_at'])`, `insertMany()` for bulk |
| test/demo data | factories + seeders (`make:factory`, `db:seed`) |
| your API's error wording | `config/app.php` `errors` (pype's own errors only; the keys are listed there) |
| logging | inject `Psr\Log\LoggerInterface` |

## Testing

`composer test`. Tests extend `Tests\TestCase` (fresh in-memory database per test,
`$this->client` = `TestClient`). Forms: `$this->client->submit('/page', '/action', [...fields])` (takes the
CSRF token like a browser); signed in: `$this->client->actingAs($user)`; check with the chainable
`assertOk()`, `assertRedirect('/x')`, `assertSee()`, `assertJsonPath()`, `assertInvalid('field')` and friends. Set up data with factories, not HTTP calls; read sent mail with
`$this->app->container->get(Pype\Mail\Transport::class)->messages()`. Test a stage (or a few) without
HTTP with `Pype\Testing\Chain::of([...], $this->app)->with($user)->params([...])->run()` and look at
`->context()` (see `tests/Unit/FindUserTest.php`). See GUIDE "Testing".

## Settings

Everything switchable is in `.env` (see `.env.example`):
- `DB_CONNECTION` (sqlite/mysql/pgsql), `SESSION_DRIVER` (database/file);
- `RATE_LIMIT_DRIVER` (database/apcu), `CACHE_DRIVER` (file/apcu/database/array/null), `QUEUE_DRIVER` (sync/database);
- `APP_SERVER` (fpm/swoole), `APP_DEBUG`.

`config/*.php` are plain arrays.

## Commands

```bash
php bin/pype serve       # dev server (or OpenSwoole with APP_SERVER=swoole)
php bin/pype routes      # every route with its compiled stages
php bin/pype migrate
composer test            # PHPUnit; tests use Pype\Testing\TestClient (in-process, keeps cookies)
```

Don't commit or push unless asked.
