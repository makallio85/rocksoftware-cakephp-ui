<?php
/**
 * What a list or a section shows when it has nothing yet: a title, a line
 * of explanation and optionally an icon and an action.
 *
 *     <?= $this->element('ui/empty_state', [
 *         'title' => 'No messages yet',
 *         'text' => 'Messages appear here once an application sends one.',
 *     ]) ?>
 *
 * @var \Cake\View\View $this
 * @var string $title The headline.
 * @var string|null $text The explanation.
 * @var string|null $icon A Bootstrap Icons class, e.g. `bi bi-inbox`.
 * @var string|null $action Markup under the text, such as a button.
 */
?>
<div class="kk-empty-state">
    <?php if (isset($icon) && (string)$icon !== '') : ?>
    <i class="kk-empty-state-icon <?= h((string)$icon) ?>" aria-hidden="true"></i>
    <?php endif; ?>
    <div class="kk-empty-state-title"><?= h((string)$title) ?></div>
    <?php if (isset($text) && (string)$text !== '') : ?>
    <div class="kk-empty-state-text"><?= h((string)$text) ?></div>
    <?php endif; ?>
    <?php if (isset($action) && (string)$action !== '') : ?>
    <div class="kk-empty-state-action"><?= (string)$action ?></div>
    <?php endif; ?>
</div>
