<?php
/** @var list<Pype\Routing\Route> $routes */
$this->layout('layouts/app');

$describe = static fn (mixed $stage): string => match (true) {
    is_string($stage) => str_contains($stage, '\\') ? substr(strrchr($stage, '\\'), 1) : $stage,
    $stage instanceof Closure => 'fn',
    default => (new ReflectionClass($stage))->getShortName(),
};
?>
<h1>Your pype app is running.</h1>
<p class="lead">
    Every route is its own pipeline. A request goes in one end, passes through the stages
    you list, and comes out as data — JSON by default, or HTML like this page.
</p>

<div class="flow" aria-label="Request lifecycle">
    <span class="edge">Request</span><i>→</i>
    <span>RequestId</span><i>→</i>
    <span>Cors</span><i>→</i>
    <span>your stages</span><i>→</i>
    <span>Formatter</span><i>→</i>
    <span class="edge">Response</span>
</div>

<div class="grid">
    <div class="card">
        <h3>Routes</h3>
        <p>Define pipelines in <code>routes/api.php</code>.</p>
    </div>
    <div class="card">
        <h3>Stages</h3>
        <p>Reusable steps live in <code>app/Stages</code>. Create one with <code>php bin/pype make:stage</code>.</p>
    </div>
    <div class="card">
        <h3>Views</h3>
        <p>Plain PHP templates in <code>resources/views</code>. End a route with <code>new View('name')</code>.</p>
    </div>
</div>

<h2>Routes in this app</h2>
<div class="card">
    <table class="routes">
        <?php foreach ($routes as $route): ?>
            <tr>
                <td><span class="badge"><?= e($route->method()) ?></span></td>
                <td>
                    <?php if ($route->method() === 'GET' && !str_contains($route->path(), '{')): ?>
                        <a href="<?= e($route->path()) ?>"><?= e($route->path()) ?></a>
                    <?php else: ?>
                        <?= e($route->path()) ?>
                    <?php endif ?>
                </td>
                <td><?= e(implode(' → ', array_map($describe, $route->stages()))) ?></td>
            </tr>
        <?php endforeach ?>
    </table>
</div>

<h2>Try the API</h2>
<pre><code>curl -X POST localhost:8000/auth/register \
  -H 'Content-Type: application/json' \
  -d '{"name":"Ada","email":"ada@example.com","password":"correct-horse"}'

curl localhost:8000/me -H 'Authorization: Bearer &lt;token&gt;'</code></pre>
