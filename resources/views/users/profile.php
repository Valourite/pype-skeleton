<?php
/** @var App\Data\User $data */
$this->layout('layouts/app', ['title' => $data->name]);

$initials = implode('', array_map(
    static fn (string $part) => mb_strtoupper(mb_substr($part, 0, 1)),
    array_slice(preg_split('/\s+/', trim($data->name)) ?: [], 0, 2),
));
?>
<div class="card">
    <div class="profile">
        <div class="avatar" aria-hidden="true"><?= e($initials) ?></div>
        <div>
            <h1><?= e($data->name) ?></h1>
            <a href="mailto:<?= e($data->email) ?>"><?= e($data->email) ?></a>
        </div>
    </div>

    <dl class="meta">
        <dt>User ID</dt>
        <dd>#<?= e($data->id) ?></dd>
        <?php if ($data->created_at !== null): ?>
            <dt>Member since</dt>
            <dd><?= e(date('j F Y', strtotime($data->created_at))) ?></dd>
        <?php endif ?>
    </dl>
</div>

<p class="muted" style="margin-top: 1.25rem">
    Same stages as <code>GET /users/<?= e($data->id) ?></code> — only the exit formatter differs.
</p>
