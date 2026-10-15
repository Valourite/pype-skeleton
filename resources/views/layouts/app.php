<?php
/**
 * Base layout. Child views call $this->layout('layouts/app', ['title' => '...']);
 * their output arrives here as $content.
 *
 * @var string      $content
 * @var string|null $title
 */
$appName = env('APP_NAME', 'pype');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(isset($title) ? "{$title} · {$appName}" : $appName) ?></title>
    <link rel="stylesheet" href="/css/app.css">
</head>
<body>
    <header class="site-header">
        <div class="container">
            <a class="brand" href="/"><span class="brand-mark">p</span><?= e($appName) ?></a>
            <nav class="nav">
                <a href="/">Home</a>
                <a href="/account">Account</a>
                <a href="/health">API</a>
                <a href="https://github.com/Valourite/pype-framework">Docs</a>
            </nav>
        </div>
    </header>

    <main>
        <div class="container">
            <?= $content ?>
        </div>
    </main>

    <footer class="site-footer">
        <div class="container">Built with pype · PHP <?= e(PHP_VERSION) ?></div>
    </footer>
</body>
</html>
