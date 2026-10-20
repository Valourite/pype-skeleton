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

## The guide

Everything pype offers, with examples (routes, stages, validation, auth and JWT, mail, cache,
queue, schedule, database, factories and seeders, testing, production...) is in the framework's
guide: **`vendor/pype/framework/docs/GUIDE.md`** in your app, or
[on GitHub](https://github.com/Valourite/pype-framework/blob/main/docs/GUIDE.md).

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
