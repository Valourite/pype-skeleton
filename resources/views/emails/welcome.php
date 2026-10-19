<?php
/** @var App\Data\User $user  @var string $link */
?>
<!doctype html>
<html lang="en">
<body style="margin:0;padding:24px;background:#f7f7f8;font-family:system-ui,-apple-system,'Segoe UI',sans-serif;color:#1d1d22">
    <div style="max-width:520px;margin:0 auto;background:#fff;border:1px solid #e3e3e8;border-radius:8px;padding:28px">
        <h1 style="font-size:20px;margin:0 0 12px">Welcome, <?= e($user->name) ?>!</h1>
        <p style="margin:0 0 16px;line-height:1.5">Your account is ready. Please confirm your email address so we know it's really you.</p>
        <p style="margin:0 0 20px"><a href="<?= e($link) ?>" style="display:inline-block;background:#2f6fdb;color:#fff;text-decoration:none;padding:10px 16px;border-radius:6px">Confirm my email</a></p>
        <p style="margin:0;color:#6b6b76;font-size:13px">The link works for 24 hours. You're getting this because you signed up with <?= e($user->email) ?>.</p>
    </div>
</body>
</html>
