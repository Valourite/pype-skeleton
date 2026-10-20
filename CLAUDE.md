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
- **Do one thing per stage**, and reuse stages across routes: `Validate` → load something →
  authorize → handler. Stages live in `app/Stages/` (`php bin/pype make:stage Name`).
- **Handlers** are invokable classes or closures at the end of the route. Their parameters are
  injected by type or route-param name: `fn (User $user, int $id, Connection $db) => ...`.
  Return data, and let the outlet format it.
- **Hand data between stages via the request context:** `$request->context()->put($thing)`,
  then a later stage or handler takes it as a typed parameter. Never put per-request data in a
  service, singleton or static: the app may run under OpenSwoole, where one process serves many
  requests.
- **Pages:**
  - `new View('template')` as the route's outlet; templates are plain PHP in
    `resources/views` (`e()` to escape, `$this->csrf()`, `$this->old()`, `$this->error()`);
  - for a page with its own logic, a class extending `Pype\View\ViewOutlet`.
- **Forms:**
  - POST with `<?= $this->csrf() ?>`;
  - PUT/PATCH/DELETE via `<?= $this->method('PATCH') ?>`;
  - reuse an API handler with `new RedirectOnSuccess('/where', 'Saved.')`.
- **Database:**
  - type-hint `Pype\Database\Connection`;
  - use `$db->table(...)` (query builder) and plain data classes (`->as(Thing::class)`). There
    is no ORM by design;
  - migrations: `php bin/pype make:migration create_things_table` writes a schema-builder
    migration that runs on SQLite, MySQL and Postgres, with a `down()` that undoes it
    (`migrate:rollback`). Once a migration ran in production, change things with a new one.
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
| validate input (incl. `unique:`/`exists:`, dates, arrays, conditional) | `new Validate([...])` / a `Validate` subclass; `status:` for 400; `TrimStrings` already runs globally. `min`/`max` inclusive; rules can't see the user (per-user unique: index + handler check) |
| who the user is | `'auth'` stack (session or API token); `Pype\Auth\JwtGuard` for JWTs; `Authenticate(redirectTo:)` for pages |
| **records that belong to a user** | load them in a stage and give **404 (not 403) when it isn't theirs**, so ids don't leak; put the record in the context for the handler (GUIDE "Roles, ownership..."). No roles system: admin = a column + a stage |
| rate limiting | `new Throttle(maxAttempts, decaySeconds)`: per IP + route; per user = a small stage (GUIDE "Roles, ownership and per-user limits") |
| signed links, named routes | `url('name', [...])`, `signed_url(...)` + `ValidateSignature` |
| cache | `Pype\Cache\Cache`: `remember`, locks, `stale()`; per-user data in `group("user.{$id}")`, **flush the group after every write** to that user's data |
| mail | `Mailer::send()`, `later()` (after the response), `queue()` (worker, retries: only off the request with `QUEUE_DRIVER=database` + `queue:work`; the default `sync` sends at once) |
| background jobs, cron | `Pype\Queue\Queue` (`make:job`), `routes/schedule.php` |
| encrypt a value | `Pype\Security\Encrypter` |
| inserts | `insert()` (id), `insertReturning([...], ['id', 'created_at'])`, `insertMany()` for bulk |
| test/demo data | factories + seeders (`make:factory`, `db:seed`) |
| your API's error wording | `config/app.php` `errors` (pype's own errors only; the keys are listed there) |
| logging | inject `Psr\Log\LoggerInterface` |

## Testing

`composer test`. Tests extend `Tests\TestCase` (fresh in-memory database per test,
`$this->client` = `TestClient`). Set up data with factories, not HTTP calls; read sent mail with
`$this->app->container->get(Pype\Mail\Transport::class)->messages()`. See GUIDE "Testing".

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
