<?php $this->layout('layouts/app', ['title' => 'Create account']); ?>
<div class="auth-card card">
    <h1>Create account</h1>

    <form method="post" action="/register" class="form">
        <?= $this->csrf() ?>
        <?= $this->partial('partials/field', ['name' => 'name', 'label' => 'Name', 'autocomplete' => 'name']) ?>
        <?= $this->partial('partials/field', ['name' => 'email', 'label' => 'Email', 'type' => 'email', 'autocomplete' => 'email']) ?>
        <?= $this->partial('partials/field', ['name' => 'password', 'label' => 'Password', 'type' => 'password', 'autocomplete' => 'new-password', 'hint' => 'At least 8 characters.']) ?>
        <button type="submit" class="button">Create account</button>
    </form>

    <p class="muted small">Already registered? <a href="/login">Sign in</a>.</p>
</div>
