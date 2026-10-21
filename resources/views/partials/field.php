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
$message = $error($name);   // $error is the helper; don't overwrite it
?>
<label class="field<?= $message ? ' has-error' : '' ?>">
    <span><?= e($label) ?></span>
    <input name="<?= e($name) ?>" type="<?= e($type) ?>"
           <?php if ($type !== 'password'): ?>value="<?= e($old($name)) ?>"<?php endif ?>
           <?php if (isset($autocomplete)): ?>autocomplete="<?= e($autocomplete) ?>"<?php endif ?>
           <?php if ($message): ?>aria-invalid="true"<?php endif ?> required>
    <?php if ($message): ?>
        <small class="field-error"><?= e($message) ?></small>
    <?php elseif (isset($hint)): ?>
        <small class="muted"><?= e($hint) ?></small>
    <?php endif ?>
</label>
