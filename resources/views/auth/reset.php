<?php
/** @var string $token  @var bool $valid */
$this->layout('layouts/app', ['title' => 'Choose a new password']);
?>
<div class="auth-card card">
    <h1>Choose a new password</h1>
    <?php if (!$valid): ?>
        <p class="notice">This reset link is invalid or has expired.</p>
        <p><a class="button" href="<?= url('password.forgot') ?>">Send me a new link</a></p>
    <?php else: ?>
        <form method="post" action="<?= url('password.update') ?>" class="form">
            <input type="hidden" name="_token" value="<?= e($csrf) ?>">
            <input type="hidden" name="token" value="<?= e($token) ?>">
            <?= $this->partial('partials/field', ['name' => 'password', 'label' => 'New password', 'type' => 'password', 'autocomplete' => 'new-password']) ?>
            <?= $this->partial('partials/field', ['name' => 'password_confirmation', 'label' => 'Repeat it', 'type' => 'password', 'autocomplete' => 'new-password']) ?>
            <button type="submit" class="button">Change password</button>
        </form>
    <?php endif ?>
</div>
