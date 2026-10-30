<?php
/**
 * A boxed note inside a page: something the reader should know about what
 * the page shows, in one of `UiHelper::NOTICE_TONES`, with an optional
 * title, icon and action.
 *
 *     <?= $this->element('ui/notice', [
 *         'tone' => 'warning',
 *         'title' => 'The draft is out of date.',
 *         'text' => 'The entry changed after the proposal was made.',
 *         'icon' => 'bi bi-exclamation-triangle',
 *         'action' => $this->Html->link('Compare', ['action' => 'compare', 12], ['class' => 'btn btn-sm btn-outline-secondary']),
 *     ]) ?>
 *
 * The colours are set by the plugin (`.kk-notice-<tone>`), as badges' are.
 * A `danger` notice has `role="alert"`, every other `role="note"`; `role`
 * sets another (`status` for one a script updates).
 *
 * @var \Cake\View\View $this
 * @var string|null $tone `ok`, `pending`, `danger`, `muted`, `info` (the default), `tag` or `warning`.
 * @var string|null $title A short heading, shown bold above the text.
 * @var string|null $text The note, escaped here.
 * @var string|null $html The note as markup the caller escaped, in place of `text`.
 * @var string|null $action Markup beside the text (under it on phones), such as a button.
 * @var string|null $icon A Bootstrap Icons class, e.g. `bi bi-info-circle`.
 * @var string|null $role The ARIA role, when the tone's is not the right one.
 * @var string|null $class More classes on the box, such as `mb-3`.
 * @var string|null $id The box's id.
 */
use RockSoftware\Ui\View\Helper\UiHelper;

$tone = isset($tone) && (string)$tone !== '' ? (string)$tone : 'info';
if (!in_array($tone, UiHelper::NOTICE_TONES, true)) {
    throw new InvalidArgumentException(sprintf(
        'Unknown notice tone "%s"; use one of %s.',
        $tone,
        implode(', ', UiHelper::NOTICE_TONES),
    ));
}
$role = isset($role) && (string)$role !== '' ? (string)$role : ($tone === 'danger' ? 'alert' : 'note');
$class = trim('kk-notice kk-notice-' . $tone . ' ' . (string)($class ?? ''));
$title = (string)($title ?? '');
$body = isset($html) && (string)$html !== '' ? (string)$html : h((string)($text ?? ''));
$action = (string)($action ?? '');
$icon = (string)($icon ?? '');
$idAttribute = isset($id) && (string)$id !== '' ? ' id="' . h((string)$id) . '"' : '';
?>
<div class="<?= h($class) ?>" role="<?= h($role) ?>"<?= $idAttribute ?>>
    <?php if ($icon !== '') : ?>
    <i class="kk-notice-icon <?= h($icon) ?>" aria-hidden="true"></i>
    <?php endif; ?>
    <div class="kk-notice-body">
        <?php if ($title !== '') : ?>
        <div class="kk-notice-title"><?= h($title) ?></div>
        <?php endif; ?>
        <?php if ($body !== '') : ?>
        <div class="kk-notice-text"><?= $body ?></div>
        <?php endif; ?>
    </div>
    <?php if ($action !== '') : ?>
    <div class="kk-notice-action"><?= $action ?></div>
    <?php endif; ?>
</div>
