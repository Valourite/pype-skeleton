<?php
/**
 * A labelled input that refills itself and shows its error after a failed submit.
 *
 * @var string      $name
 * @var string      $label
 * @var string|null $type
 * @var string|null $autocomplete
 * @var string|null $hint
 */
$type ??= 'text';
$error = $this->error($name);
?>
<label class="field<?= $error ? ' has-error' : '' ?>">
    <span><?= e($label) ?></span>
    <input name="<?= e($name) ?>" type="<?= e($type) ?>"
           <?php if ($type !== 'password'): ?>value="<?= e($this->old($name)) ?>"<?php endif ?>
           <?php if (isset($autocomplete)): ?>autocomplete="<?= e($autocomplete) ?>"<?php endif ?>
           <?php if ($error): ?>aria-invalid="true"<?php endif ?> required>
    <?php if ($error): ?>
        <small class="field-error"><?= e($error) ?></small>
    <?php elseif (isset($hint)): ?>
        <small class="muted"><?= e($hint) ?></small>
    <?php endif ?>
</label>
