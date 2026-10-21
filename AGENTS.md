# Working on this app

This app runs on **pype** (`pype/framework`). This file holds the rules for building it. The full
reference, with an example of every built-in, is **`vendor/pype/framework/docs/GUIDE.md`**. Read its
section for what you're about to build (start with "Building a feature, start to finish"). The guide
is updated with every framework release, but this file stays as it was when the app was created, so
where the two differ, the guide is right.

## How pype works

A route is a **pipeline**: an ordered list of small classes (**stages**). Each does one thing and hands
typed objects to the next through the **request context** (`$request->context()->put($quote)`, later
`->get(Quote::class)`). An **outlet** at the end writes the result: JSON by default, a page, or a
redirect. Route files stay short and list class names. Every step is a class, so it can be tested on
its own. If a stage asks for something nothing put in the context, it fails with "Context has no [X]".

## The shape of a feature

```php
// routes/quotes/web.php (php bin/pype make:routes quotes)
$router->group('/quotes', ['web', Authenticate::with(redirectTo: '/login')], function (Router $router) {
    $router->get('/', [LoadQuotes::class, QuotesView::class])->name('index');
    $router->group('/{id}', [LoadQuote::class], function (Router $router) {           // 404 if not theirs
        $router->get('/', [QuoteView::class])->name('show');
        $router->patch('/', [ValidateQuote::class, UpdateQuote::class, RedirectOutlet::with('quotes.show', flash: 'Saved.')])->name('update');
    });
}, name: 'quotes.');
```

guard → validate → load → do the work → outlet. Stages live in `app/Stages`, handlers in
`app/Handlers`, page outlets in `app/Views`. A JSON route uses the same stages and ends with a stage
that returns the data.

**Don't:**
- **Build a generic "context view" or a bag of view data.** Write one page outlet per page
  (`make:view`).
- **Write stages that only exist to feed template variables.** Load the real object.
- **Make your own stages `ContainerAware`.** Take services in the constructor and arguments through
  `X::with(...)`.
- **Put logic in route closures.** A one-line `fn (Quote $q) => $q` that ends a JSON route is fine.
- **Write a repository class per table.** Use `$db->table()` in stages and handlers, and a plain
  service for logic several handlers share.
- **Return 403 for someone else's record.** A `Load` stage gives 404.
- **Keep per-request data in a service, singleton or static.** It belongs in the context, because
  under Swoole one process serves many requests.
- **Write your own version of a built-in.** Check the table below first.

## Rules

- **Stages in a route:** write `X::class`, or `X::with(arg: ...)` when it takes arguments (plain data
  only; services are autowired). `new X(...)` works, but it's built while the routes file loads and is
  shared by every request a worker serves.
- **Declare what a stage reads and puts:** `#[Requires(User::class)]`, `#[Provides(Quote::class)]`.
  `php bin/pype routes:check` (and `tests/Feature/RoutesTest.php`) catches a stage listed before what
  it needs.
- **Routes:**
  - Every file in `routes/` loads.
  - `api.php` files get `/api` and `api.` names.
  - Use `make:routes quotes` for a feature folder and `resource()` / `apiResource()` for the usual set.
  - Name routes you link to, and link with `url('name', [...])`.
- **Pages:**
  - Templates live in `resources/views`. The extension picks the engine: `.php`, `.latte`
    (latte/latte) or `.blade.php` (eftec/bladeone).
  - Every template gets `$csrf`, `$old('field')`, `$error('field')` and `$errors`. Write
    `$message = $error('x')`, never `$error = ...`, which replaces the helper.
- **Forms:**
  - Add `<input type="hidden" name="_token" value="<?= e($csrf) ?>">`, plus
    `<input type="hidden" name="_method" value="PATCH">` for other verbs.
  - After success, use `RedirectOutlet::with(...)` or `return redirect('name', [...], flash: '...')`.
- **Database:**
  - Type-hint `Connection` and use `$db->table(...)->as(Thing::class)`. There's no ORM.
  - Create migrations with `make:migration`. Each has a `down()`. Once a migration has run in
    production, change things with a new one.
- **Prefer native PHP** over packages.

## Built-ins: check here before writing your own

| Need | Use |
|---|---|
| validate input | `Validate::with([...])` or a `Validate` subclass. Per user/company: `unique:items,sku,{id},id,company_id={user.company_id}` (also `exists:`), or `rulesFor(Request)`. Forms with rows: `prepare()` drops empty rows and keeps the keys |
| who the user is | the `'auth'` stack (session or token); `Authenticate::with(redirectTo: '/login')` for pages; `JwtGuard` |
| records that belong to someone | a `Load` stage: 404 when it isn't theirs, then puts it in the context |
| rate limiting | `Throttle::with(5, 60)` per IP; `by: User::class` per user; `key: 'send'` to share a counter |
| redirect after a form | `RedirectOutlet::with('name', flash: '...')`, or `redirect(...)` from any stage |
| lists | `->paginate(25, $request)`, then `$page->links()` in the view; `simplePaginate()` without a count |
| signed links | `signed_url('name', [...], expiresIn: 3600)` + `ValidateSignature` |
| cache | `Pype\Cache\Cache` (`remember`, locks, groups: flush a user's group after writes) |
| mail | `Mailer::send()`, `later()`, `queue()`. While developing, read sent mail and click its links at `/_pype/mail` |
| jobs, cron | `Pype\Queue\Queue` (`make:job`), `routes/schedule.php` |
| encryption | `Pype\Security\Encrypter` |
| test/demo data | factories + seeders (`make:factory`, `db:seed`); `Fake`, or Faker if installed |
| logs | inject `Psr\Log\LoggerInterface`; view them at `/_pype/logs` |

## Testing

`composer test`. Tests extend `Tests\TestCase`, which gives a fresh in-memory database and `$this->client`.

- **Data:** set it up with factories, not HTTP calls.
- **Forms and sign-in:** `$this->client->submit('/page', '/action', [...])` and `->actingAs($user)`.
- **Assertions:** `assertOk()`, `assertRedirect()`, `assertSee()`, `assertJsonPath()`,
  `assertInvalid('field')`.
- **One stage alone:** `Pype\Testing\Chain::of([...], $this->app)->with($user)->params([...])->run()`
  (see `tests/Unit/FindUserTest.php`).

## Commands

```bash
php bin/pype serve             # dev server (APP_SERVER=swoole for OpenSwoole/Swoole)
php bin/pype routes            # every route and its stages
php bin/pype routes:check      # stage order, without running anything
php bin/pype make:routes|make:stage|make:view|make:migration|make:factory|make:job Name
php bin/pype migrate
php bin/pype install           # add Latte, Blade or Faker
composer test
```

Settings are in `.env` (see `.env.example`); `config/*.php` are plain arrays.

Don't commit or push unless asked.
