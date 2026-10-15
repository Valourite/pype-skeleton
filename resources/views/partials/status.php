<?php if ($status = $this->session()?->get('status')): ?>
    <p class="notice" role="status"><?= e($status) ?></p>
<?php endif ?>
