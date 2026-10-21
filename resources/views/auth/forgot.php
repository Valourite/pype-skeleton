<?php $this->layout('layouts/app', ['title' => 'Forgot password']); ?>
<div class="auth-card card">
    <h1>Forgot your password?</h1>
    <?= $this->partial('partials/status') ?>
    <p class="muted">Enter your email and we'll send you a link to choose a new one.</p>

    <form method="post" action="<?= url('password.email') ?>" class="form">
        <input type="hidden" name="_token" value="<?= e($csrf) ?>">
        <?= $this->partial('partials/field', ['name' => 'email', 'label' => 'Email', 'type' => 'email', 'autocomplete' => 'email']) ?>
        <button type="submit" class="button">Send reset link</button>
    </form>

    <p class="muted small">Remembered it? <a href="<?= url('login') ?>">Sign in</a>.</p>
</div>
