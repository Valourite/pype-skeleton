<?php
/** @var App\Data\User $user */
$this->layout('layouts/app', ['title' => 'Account']);
?>
<?= $this->partial('partials/status') ?>
<div class="card">
    <h1><?= e($user->name) ?></h1>
    <dl class="meta">
        <dt>Email</dt>
        <dd><?= e($user->email) ?></dd>
        <dt>Member since</dt>
        <dd><?= e(date('j F Y', strtotime($user->created_at))) ?></dd>
    </dl>

    <form method="post" action="<?= url('logout') ?>" class="inline-form">
        <?= $this->csrf() ?>
        <button type="submit" class="button secondary">Sign out</button>
    </form>
</div>

<p class="muted small" style="margin-top: 1.25rem">
    Signed in with a session cookie. <code>GET /me</code> returns the same user to an API client with a bearer token.
</p>
