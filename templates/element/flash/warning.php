<?php
/**
 * A flash message of type `warning`, as a Bootstrap alert.
 *
 * @var \Cake\View\View $this
 * @var array<string, mixed> $params
 * @var string $message
 */
if (!isset($params['escape']) || $params['escape'] !== false) {
    $message = h($message);
}
?>
<div class="alert alert-warning alert-dismissible fade show d-flex align-items-start gap-2" role="alert">
    <i class="bi bi-exclamation-triangle mt-1"></i>
    <div><?= $message ?></div>
    <button type="button" class="btn-close" data-bs-dismiss="alert"
            aria-label="<?= h(__d('rock_software_ui', 'Close')) ?>"></button>
</div>
