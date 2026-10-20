# Notes for working on a pype app

This app runs on **pype** (`pype/framework`): each route is a pipeline of **stages**, and an
**outlet** at the end writes the result (JSON by default, HTML via views). `README.md` covers
everything a developer uses. Framework internals and design rules are in the framework repo
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
- **Background work:** slow or retryable work goes in a job (`php bin/pype make:job Name`,
  `app/Jobs/`), pushed with `Pype\Queue\Queue`; mail with `$mailer->queue(...)`. Recurring
  tasks go in `routes/schedule.php`.
- **Prefer native PHP** (`password_hash`, `filter_var`, `DateTimeImmutable`, ...) over pulling in
  packages. pype aims to stay fast and feel like PHP.

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
