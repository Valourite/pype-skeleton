<?php
/**
 * Rendered by App\Views\UserProfile.
 *
 * @var App\Views\UserProfile $view
 * @var App\Data\User         $user
 * @var string|null           $memberSince
 */
$this->layout('layouts/app', ['title' => $user->name]);
?>
<div class="card">
    <div class="profile">
        <div class="avatar" aria-hidden="true"><?= e($view->initials($user)) ?></div>
        <div>
            <h1><?= e($user->name) ?></h1>
            <a href="mailto:<?= e($user->email) ?>"><?= e($user->email) ?></a>
        </div>
    </div>

    <dl class="meta">
        <dt>User ID</dt>
        <dd>#<?= e($user->id) ?></dd>
        <?php if ($memberSince !== null): ?>
            <dt>Member since</dt>
            <dd><?= e($memberSince) ?></dd>
        <?php endif ?>
    </dl>
</div>

<p class="muted" style="margin-top: 1.25rem">
    Same stages as <code>GET /users/<?= e($user->id) ?></code> — only the outlet differs.
</p>
