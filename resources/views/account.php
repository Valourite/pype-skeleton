<?php
/** @var App\Data\User $user */
$this->layout('layouts/app', ['title' => 'Account']);
?>
<?= $this->partial('partials/status') ?>
<?php if ($user->email_verified_at === null): ?>
    <div class="notice with-action">
        <span>Please confirm your email address: we sent a link to <?= e($user->email) ?>.</span>
        <form method="post" action="<?= url('verification.send') ?>">
            <input type="hidden" name="_token" value="<?= e($csrf) ?>">
            <button type="submit" class="button secondary">Send it again</button>
        </form>
    </div>
<?php endif ?>
<div class="card">
    <h1><?= e($user->name) ?></h1>
    <dl class="meta">
        <dt>Email</dt>
        <dd><?= e($user->email) ?></dd>
        <dt>Verified</dt>
        <dd><?= $user->email_verified_at === null ? 'Not yet' : e(date('j F Y', strtotime($user->email_verified_at))) ?></dd>
        <dt>Member since</dt>
        <dd><?= e(date('j F Y', strtotime($user->created_at))) ?></dd>
    </dl>

    <form method="post" action="<?= url('logout') ?>" class="inline-form">
        <input type="hidden" name="_token" value="<?= e($csrf) ?>">
        <button type="submit" class="button secondary">Sign out</button>
    </form>
</div>

<p class="muted small" style="margin-top: 1.25rem">
    Signed in with a session cookie. <code>GET /api/me</code> returns the same user to an API client with a bearer token.
</p>
