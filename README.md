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
| `'auth'` / `new Authenticate(...)`  | require a user, found by guards (session, bearer token, or your own)    |
| `'web'`                             | session cookie + CSRF check + validation errors back to the form         |
| `Pype\Auth\Auth`                    | `createUser`, `attempt`, `issueToken`, `revoke`, `revokeAll`            |
| `new Throttle(5, 60)`               | rate limit per IP + route (429 + `Retry-After`); counters in the database or APCu (`RATE_LIMIT_DRIVER`) |
| `new Validate([...rules])`          | 422 on bad input; `ValidatedInput` injected on success (see Validation) |
| `Pype\Cache\Cache`                 | `remember`, locks, background refresh, groups; file, APCu or database (see Cache) |
| `Pype\Queue\Queue`                 | background jobs: `push`, `later`, retries, `queue:work` (see Queue); `routes/schedule.php` for cron tasks |
| `new RequestId()`                   | `X-Request-Id` on every response                                        |
| `new Cors([...origins])`            | CORS headers + preflight                                                |
| `new JsonBody(maxBytes: ...)`       | 413 on big bodies, 400 on malformed JSON                                |
| `new MethodOverride()` (global)     | forms send PUT/PATCH/DELETE via a hidden `_method` field                |
| `new RedirectOnSuccess($to, $msg)`  | reuse an API handler from a form: redirect + flash on success           |

## Two ways in: pages and JSON

The same users and stages serve both. Only the first and last stages differ.

- **`routes/web.php`**: browser pages. The `'web'` stack starts a session (cookie), checks the CSRF
  token on every form post, and sends a failed form back to itself with errors and old input.
  `/login`, `/register`, `/account`, `POST /logout`.
- **`routes/api.php`**: JSON for apps and scripts, authenticated with bearer tokens.
  `POST /auth/register`, `POST /auth/login`, `POST /auth/logout`, `GET /me`.

Auth isn't tied to either. `Authenticate` asks a list of *guards* who the user is (from
`config/auth.php`: session, then bearer token). How a user proves who they are is your
handler's job; `SessionGuard::login()` just remembers the result. For anything else (API keys,
JWT, a header from your proxy), implement `Pype\Auth\Guard`:

```php
$router->get('/account', ['web', new Authenticate(redirectTo: '/login'), ...]);    // browsers
$router->get('/me', ['auth', fn (User $user) => $user]);                             // tokens
$router->post('/hooks', [new Authenticate(guards: [ApiKeyGuard::class]), ...]);      // your own
```

Forms in templates:

```php
<form method="post" action="/login">
    <?= $this->csrf() ?>
    <input name="email" value="<?= e($this->old('email')) ?>">
    <?= e($this->error('email')) ?>
</form>
```

A session cookie only authenticates routes in the `'web'` stack, so every cookie-authenticated
form post is CSRF-checked.

## Validation

Validate as the first stage of a route. Bad input is a 422 (or, in the `'web'` stack, back to
the form with errors); good input arrives as `ValidatedInput`:

```php
$router->post('/register', [
    new Validate([
        'name' => 'required|string|max:100',
        'email' => 'required|email|unique:users',
        'password' => 'required|string|min:8|confirmed',
        'terms' => 'accepted',
    ], messages: ['email.unique' => 'That email is already registered.']),
    Register::class,
]);
```

| Kind | Rules |
|---|---|
| Presence | `required`, `nullable`, `required_if:type,company`, `required_with:password` |
| Types | `string`, `int`, `numeric`, `bool`, `array`, `accepted`, `json` |
| Size | `min:n`, `max:n`, `between:a,b` (length, value, item count, or KB for files), `digits:n` |
| Format | `email`, `url`, `ip`, `uuid`, `alpha`, `alpha_num`, `alpha_dash`, `regex:/.../`, `date`, `date:d/m/Y` |
| Compare | `in:a,b`, `not_in:a,b`, `same:field`, `different:field`, `confirmed`, `before:` / `after:` / `before_or_equal:` / `after_or_equal:` (a date, `today`, or another field) |
| Files | `file`, `image`, `mimes:pdf,jpg` (the real type, not what the browser claims) |
| Database | `unique:users,email`, `exists:categories,id`; `{id}` is the route param, so `unique:users,email,{id}` lets an edit form keep its own email |

- **Arrays:** `'tags.*' => 'string|max:20'`, `'items.*.qty' => 'required|int|min:1'`; errors are keyed `items.0.qty`.
- **Your own rules:** a closure `fn ($value, $field, $data) => $ok ? null : 'message'`, or a class implementing
  `Pype\Validation\Rule`. Scope database rules with objects: `new Unique('categories', 'name', where: ['user_id' => $user->id])`.
- **A regex containing `|`** must use the array form: `['regex:/^(cat|dog)$/']`.

## Forgotten passwords and email verification

Both flows are app code you can change (routes in `routes/web.php`, handlers in
`app/Handlers/Web`, emails in `app/Mail`, pages in `resources/views/auth`). The parts that are
easy to get wrong are in the framework:

- **Password reset:** `/password/forgot` emails a link; `/password/reset/{token}` sets a new
  password. `Pype\Auth\PasswordResets` stores only a hash of the token, accepts it once and for
  60 minutes, sends at most one link a minute per user, and revokes the user's API tokens after a
  reset. The form answers the same whether or not the email has an account.
- **Email verification:** the welcome email links to a signed URL (`ValidateSignature`, valid
  for a day, tied to the address so changing the email voids it). `/account` shows a reminder
  with a "send it again" button. To require a verified email for some pages, add
  `new Pype\Auth\EnsureEmailIsVerified(redirectTo: '/account')` to their group (API clients get a 403).

- **Remember me:** the login checkbox keeps a device signed in for 30 days after its session
  ends. Each device has its own row in `remember_tokens` (only a hash of the cookie's secret), the
  cookie is swapped on every use, a replayed old copy signs out all remembered devices, and
  logging out or resetting the password forgets them.

Settings in `config/auth.php`: `reset_ttl` (minutes), `reset_throttle` (seconds),
`remember_table` (null turns "remember me" off), `remember_days`.

## Encryption

For data you must read back (a user's OAuth tokens, an API key you store for them):

```php
fn (Pype\Security\Encrypter $encrypter) => $db->table('integrations')->insert([
    'user_id' => $user->id,
    'token' => $encrypter->encrypt($oauthToken),   // decrypt() throws if it was changed
]);
```

It uses libsodium with your `APP_KEY`. After changing the key, put the old one in
`APP_PREVIOUS_KEYS` so existing values still decrypt. Passwords are hashed, never encrypted.

## Mail

```php
fn (Mailer $mailer, User $user) => $mailer->send(
    Message::to($user->email, $user->name)
        ->subject('Your receipt')
        ->view('emails/receipt', ['user' => $user])   // resources/views/emails/receipt.php
        ->attach($pdfPath),
);
```

For emails sent from several places, write a class (see `app/Mail/WelcomeMail.php`) and send
`new WelcomeMail($user)`. A plain-text version is made from the HTML unless you call `->text()`.

`$mailer->later(...)` sends after the response has reached the browser (under PHP-FPM and
OpenSwoole), so a slow mail server never slows the page; failures are logged.
`$mailer->send(...)` sends now and throws `Pype\Mail\MailException` if it can't.

Configure with the usual variables: `MAIL_DRIVER=smtp`, `MAIL_HOST`, `MAIL_PORT`,
`MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_ENCRYPTION` (tls/ssl/none), `MAIL_FROM_ADDRESS`. The
default `MAIL_DRIVER=log` writes emails to the log (see them in the log viewer); tests use
`array` and read them with `$app->container->get(Pype\Mail\Transport::class)->messages()`. To see
emails rendered locally, run [Mailpit](https://mailpit.axllent.org) and use `MAIL_HOST=127.0.0.1`,
`MAIL_PORT=1025`, `MAIL_ENCRYPTION=none`. Sending goes through PHPMailer.

## Cache

Type-hint `Pype\Cache\Cache` (or PSR-16's `Psr\SimpleCache\CacheInterface`):

```php
fn (User $user, Cache $cache, Reports $reports) => $cache->remember(
    "totals.{$user->id}.2026-10", 3600, fn () => $reports->totals($user->id, '2026-10'),
);
```

| Call | Does |
|---|---|
| `get`, `set($key, $value, $ttl)`, `has`, `delete`, `clear` | PSR-16; `$ttl` in seconds, a `DateInterval`, or `null` for no expiry. `null` values are cached too |
| `remember($key, $ttl, fn)` | the cached value, or the closure's result, cached |
| `add($key, $value, $ttl)` | only if missing; atomic, so of two racing requests exactly one gets `true` |
| `increment($key, $by)` / `decrement` | atomic counter, starts from 0 |
| `pull`, `forever` | get-and-delete; set with no expiry |
| `lock('send-invoices', 120)->run(fn)` | run while holding a lock (returns `null` if someone else holds it). Also `->get()` / `->release()` |
| `stale($key, fresh: 60, stale: 600, fn)` | serve a slightly old value and refresh it **after the response** has been sent; only one request refreshes |
| `group("user.{$id}")` | the same API, scoped to a group; `->flush()` empties the whole group with one write |

**Keys:** PSR-16 reserves `{}()/\@:`, so separate parts with dots: `totals.5.2026-10`. Every key
gets `CACHE_PREFIX` (default `pype.`), and `clear()` / `php bin/pype cache:clear` only remove
keys with it.

**Clearing what changed:** put a user's cached data in their group and flush it wherever the
data changes:

```php
$cache->group("user.{$user->id}")->remember('dashboard', 3600, fn () => ...);   // reading
$cache->group("user.{$user->id}")->flush();                                       // after a write
```

**Drivers** (`CACHE_DRIVER`):

| Driver | Get (hit) | Use |
|---|---|---|
| `file` (default) | ~1.4 µs | one PHP file per key in `storage/cache`; opcache keeps hits in memory. Needs nothing |
| `apcu` | ~0.4 µs | **recommended** on a single server: shared memory, no disk at all |
| `database` | a query | shared by several servers; run `php bin/pype migrate` for the `cache` table |
| `array` / `null` | | tests (phpunit.xml uses `array`) / caching off |

Things to know about **APCu**:
1. **`php bin/pype cache:clear` can't clear it.** The CLI has its own APCu memory, separate from
   PHP-FPM's, so the command says so instead of pretending. Reload PHP-FPM to clear it (deploys
   usually do); under OpenSwoole, restart the server.
2. **It is per server** (so is `file`). Behind a load balancer, use `database`.
3. **It has a fixed size**, `apc.shm_size` (32 MB by default). When it is full, old entries are
   evicted; raise it if your cache is big.
4. **Install it** with `pecl install apcu` or `apt install php-apcu`. For CLI use (commands,
   tests), also set `apc.enable_cli=1`. Without the extension, `CACHE_DRIVER=apcu` fails with a
   clear error.

With `file` under OpenSwoole, set `opcache.file_update_protection=0` in php.ini so opcache
serves cache files a worker wrote itself (safe: files appear by atomic rename). Under PHP-FPM
nothing is needed.

## Queue

Slow or unreliable work (emails, exports, calls to other services) can run in a **job** outside
the request. A job is a plain class: its constructor takes the data (ids, strings, arrays; no
services), and `handle()` gets services injected by type, like a route handler:

```php
final class SendReceipt
{
    public int $tries = 3;               // optional (default 1)
    public array $backoff = [10, 60];    // optional: seconds before each retry

    public function __construct(public readonly int $orderId) {}

    public function handle(Orders $orders, Mailer $mailer): void
    {
        $mailer->send(new ReceiptMail($orders->find($this->orderId)));
    }

    public function failed(Throwable $e): void {}   // optional: after the last try
}

fn (Queue $queue, Order $order) => $queue->push(new SendReceipt($order->id));   // Pype\Queue\Queue
$queue->push(new SendReceipt($id), 'mail');         // onto the 'mail' queue
$queue->later(300, new SendReceipt($id));           // in 5 minutes (seconds or DateInterval)
$mailer->queue(new WelcomeMail($user));             // email through the queue, 3 tries
```

`QUEUE_DRIVER=sync` (the default) runs jobs right away, so nothing needs starting while you
develop. In production set `QUEUE_DRIVER=database` (run `php bin/pype migrate` for the `jobs`
tables) and keep a worker running:

```bash
php bin/pype queue:work                          # default queue; Ctrl+C stops after the current job
php bin/pype queue:work --queue=mail,default     # 'mail' first
php bin/pype queue:failed                        # jobs that gave up, with their error
php bin/pype queue:retry 12                      # or: queue:retry all
php bin/pype queue:restart                       # on deploy: workers stop after their job
```

Workers are long-running processes, so they need a supervisor that starts them again (they
stop on `queue:restart`, `--max-jobs`, `--max-time`, `--memory`, or a job running past
`--timeout`). With systemd:

```ini
# /etc/systemd/system/myapp-queue.service
[Service]
User=www-data
WorkingDirectory=/var/www/myapp
ExecStart=/usr/bin/php bin/pype queue:work --max-time=3600
Restart=always

[Install]
WantedBy=multi-user.target
```

Run as many workers as you like (a `@` template unit, or several services): each job is
handed to exactly one of them. Jobs are stored with `serialize()`, so change a job class's
constructor carefully while old jobs are still waiting.

## Schedule

Recurring tasks live in `routes/schedule.php`:

```php
$schedule->command('reports:send')->weeklyOn(1, '08:00');           // a bin/pype command
$schedule->call(fn (SessionStore $s) => $s->gc())->hourly();        // a closure, services injected
$schedule->job(new SyncRates())->everyFifteenMinutes();             // pushed onto the queue
$schedule->call(...)->dailyAt('02:00')->withoutOverlapping()->onOneServer();
```

Frequencies: `everyMinute`, `everyFiveMinutes` (also Ten, Fifteen, Thirty), `hourly`,
`hourlyAt(17)`, `daily`, `dailyAt('13:00')`, `weekdays()`, `weekly`, `weeklyOn(1, '8:00')`,
`monthly`, `monthlyOn(15, '6:00')`, or any `cron('*/10 9-17 * * 1-5')`; `timezone()` per task
or for the whole file. `withoutOverlapping()` skips a run while the last one is still going;
`onOneServer()` runs it on only one of several servers (needs `CACHE_DRIVER=database`).

One cron line runs it all:

```
* * * * * cd /var/www/myapp && php bin/pype schedule:run >> /dev/null 2>&1
```

No cron (a container, your laptop)? `php bin/pype schedule:work` does the same in the
foreground. `php bin/pype schedule:list` shows every task, and failures go to the log.

## Logs and errors

Inject the logger anywhere (it's PSR-3, so Monolog-style calls work):

```php
fn (Psr\Log\LoggerInterface $log, User $user) => $log->warning('Card declined for {user}', ['user' => $user->id]);
```

Entries go to `storage/logs/pype-YYYY-MM-DD.log`, one JSON line each, tagged with the request id
so everything from one request can be found together. Unexpected errors are logged
automatically, and in development each request gets a line too (`GET /expenses 200 3.1 ms`).

**Sending logs elsewhere:** set `LOG_FORWARD=stderr` (or `stdout`, `syslog`) and a log agent
or your platform picks the JSON lines up for Datadog, Loki, Papertrail and the like. For a
service with a PHP SDK, put a PSR-3 logger class in `log.forward` in `config/log.php` (for
example Monolog with a Sentry handler) and raise `LOG_FORWARD_LEVEL` to `warning` so only
problems are sent. The file is always written too, so the viewer and error page keep working.

**Log viewer:** open `/_pype/logs` while `APP_DEBUG=true` for a live view (new entries appear
within a second) with filters for level, request id and text. It's off in production unless you
enable it and guard it with stages in `config/log.php` (`'stages' => ['web', 'auth', EnsureAdmin::class]`).

**Error page:** with `APP_DEBUG=true`, an unexpected error in the browser shows the exception,
the code around each line of the trace, the route and its stages, the request (passwords,
tokens and cookies hidden), and the log lines from that request. API clients still get JSON, and
production shows a plain error.

## Links

Name the routes you link to, then build links with `url()` instead of writing paths by hand:

```php
$router->get('/expenses/{id}/edit', [LoadExpense::class, new View('expenses/form')])->name('expenses.edit');

$router->group('/admin', ['auth'], function (Router $r) {
    $r->get('/users', ListUsers::class)->name('users');           // named admin.users
}, name: 'admin.');
```

```php
url('expenses.edit', ['id' => 3])              // /expenses/3/edit
url('expenses.index', ['month' => '2026-10'])  // /expenses?month=2026-10 (extra params → query)
url('home', absolute: true)                    // http://localhost:8000/ (APP_URL)
Response::redirect(url('dashboard'), 303)
```

In templates: `<a href="<?= url('expenses.edit', ['id' => $expense->id]) ?>">Edit</a>`. A missing
param or an unknown name is an error, so a renamed route can't leave broken links behind.
Prefer injecting a service? `fn (Pype\Routing\Urls $urls) => $urls->to('home')`.

**Signed links** (password reset, email verification, unsubscribe) can't be changed or reused
after they expire:

```php
$link = signed_url('password.reset', ['user' => $user->id], expiresIn: 3600);   // absolute

$router->get('/password/reset/{user}', [new ValidateSignature(), ShowResetForm::class])
    ->name('password.reset');                  // 403 if tampered with or expired
```

They're signed with `APP_KEY` (HMAC-SHA256), which `create-project` sets for you
(`php bin/pype key:generate` to make one; `--force` replaces it, which invalidates old links).

## Database

SQLite by default (`database/database.sqlite`, WAL mode for fast concurrent reads). Switch to
MySQL or Postgres with `DB_CONNECTION=mysql|pgsql` in `.env` (see `.env.example` for every
setting). Type-hint `Pype\Database\Connection` anywhere:

```php
$db->table('users')->where('id', $id)->as(User::class)->first();
$db->table('posts')->insert(['title' => 'Hello']);
$db->table('posts as p')
    ->join('users as u', 'u.id', '=', 'p.user_id')
    ->select('u.name')->selectRaw('COUNT(*) AS posts')
    ->groupBy('u.id')->orderBy('posts', 'desc')->get();
$db->table('orders')->where('user_id', $id)->sum('total');
$db->select('SELECT * FROM users WHERE email LIKE ?', ['%@example.com']);
$db->transaction(fn (Connection $db) => ...);
```

Migrations live in `database/migrations` and run in filename order. `make:migration` writes a
PHP migration using the schema builder, which produces the right SQL for SQLite, MySQL and Postgres:

```php
return new class extends Migration {
    public function up(Schema $schema): void
    {
        $schema->create('posts', function (Table $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->string('title');
            $t->text('body')->nullable();
            $t->boolean('published')->default(false);
            $t->timestamps();
            $t->index(['user_id', 'created_at']);
        });
    }

    public function down(Schema $schema): void   // undoes up(), for migrate:rollback
    {
        $schema->dropIfExists('posts');
    }
};
```

Columns: `id`, `foreignId`, `string`, `text`, `integer`, `bigInteger`, `boolean`, `decimal`,
`date`, `timestamp`, `timestamps`, `json`, `longText`. Modifiers: `nullable`, `default`, `useCurrent`,
`unique`, `primary`, `index`, `check`, `constrained`. `$schema->table(...)` adds/renames/drops
columns and indexes, and `$schema->raw($sql)` runs anything else. Prefer plain SQL? `make:migration
add_search_view --sql` writes a `.sql` file instead; both kinds can sit side by side. In a
`.sql` file, the SQL after a `-- down` line undoes the part above it.

```bash
php bin/pype migrate                  # run what's pending (one "batch")
php bin/pype migrate:rollback         # undo the last batch, newest first
php bin/pype migrate:rollback --step=1  # or just the last migration (any number)
php bin/pype migrate:status           # what ran, in which batch
php bin/pype migrate:fresh            # drop every table and migrate again (development)
```

A migration without `down()` (or a `-- down` section) can't be rolled back: rollback stops
there and says so. `migrate:fresh` doesn't need `down()`, and refuses to run when `APP_DEBUG`
is off unless you add `--force`. On MySQL each schema change is committed as it runs, so a
migration that fails halfway may leave part of its changes behind; SQLite and Postgres undo the
whole file. Once a migration has run in production, change things with a new migration rather
than rolling back: rolling back drops data.

## Running in production

The default is nothing special: point nginx (or Apache) at `public/` with PHP-FPM. On each
deploy, after `composer install --no-dev --classmap-authoritative`, run:

```bash
php bin/pype optimize      # compiles the route tables to bootstrap/cache/routes.php
php bin/pype cache:clear   # if cached data changed shape (not for CACHE_DRIVER=apcu: reload PHP-FPM)
php bin/pype queue:restart # queue workers pick up the new code
```

Also add the scheduler's cron line and a supervised `queue:work` (see Queue and Schedule).

PHP-FPM then reads the compiled tables (kept in memory by opcache) instead of rebuilding them on
every request. Forgetting it after a route change is safe: pype notices the file no longer
matches and builds the tables itself. `php bin/pype optimize:clear` removes it.

Or let OpenSwoole serve the app: it boots once and keeps it in memory, skipping PHP-FPM's
per-request start-up. Install the extension (`pecl install openswoole`), then:

```bash
APP_SERVER=swoole php bin/pype serve --host=0.0.0.0 --port=8080 --workers=8
```

It serves `public/` files too, so no nginx is needed (put one in front for TLS if you like).
Workers handle one request at a time, so your code runs exactly as under PHP-FPM. Two
differences: code changes need a restart, and anything you store in a static variable or
singleton outlives the request. Keep per-request data on the `Request` (its context), as
pype's own stages do.

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
config/       plain PHP arrays (app, auth, database, session)
database/     migrations/*.php and the SQLite file
public/       web root (index.php, css/)
resources/    views/
routes/       web.php (pages, sessions) and api.php (JSON, tokens)
tests/        PHPUnit feature tests (Pype\Testing\TestClient, in-memory SQLite)
bin/pype      CLI
vendor/       pype/framework lives here
```
