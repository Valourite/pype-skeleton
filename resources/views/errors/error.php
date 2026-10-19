<?php
/**
 * @var int                  $status
 * @var array<string, mixed> $error
 */
$this->layout('layouts/app', ['title' => "Error {$status}"]);

$fallback = match ($status) {
    401 => 'You need to sign in to see this.',
    403 => "You don't have access to this.",
    404 => "We couldn't find that page.",
    419 => 'This page expired. Go back, refresh and try again.',
    429 => 'Too many requests. Try again in a moment.',
    default => 'Something went wrong on our side.',
};
?>
<div class="error">
    <div class="code"><?= e($status) ?></div>
    <p><?= e($error['message'] ?? $fallback) ?></p>
    <a href="<?= url('home') ?>">← Back home</a>
</div>
