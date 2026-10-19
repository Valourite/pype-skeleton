<?php $this->layout('layouts/app', ['title' => 'Sign in']); ?>
<div class="auth-card card">
    <h1>Sign in</h1>
    <?= $this->partial('partials/status') ?>

    <form method="post" action="<?= url('login') ?>" class="form">
        <?= $this->csrf() ?>
        <?= $this->partial('partials/field', ['name' => 'email', 'label' => 'Email', 'type' => 'email', 'autocomplete' => 'email']) ?>
        <?= $this->partial('partials/field', ['name' => 'password', 'label' => 'Password', 'type' => 'password', 'autocomplete' => 'current-password']) ?>
        <button type="submit" class="button">Sign in</button>
    </form>

    <p class="muted small"><a href="<?= url('password.forgot') ?>">Forgot your password?</a> · No account? <a href="<?= url('register') ?>">Create one</a>.</p>
</div>
