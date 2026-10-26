<?php
/**
 * A `<kk-modal>` on a page with no Vue app of its own: the body (and footer)
 * are the page's own markup, which the modal shows while it is open, so a
 * form inside posts as usual. Any element with `data-kk-modal-open="<name>"`
 * opens it; `data-kk-modal-close` inside it closes it; a script uses
 * `window.KkUi.openModal(name)` and `closeModal(name)`. Rendering one loads
 * the `modal` component on the page; the page needs Vue (the application's,
 * or the `vue` component).
 *
 *     <?= $this->element('ui/modal', [
 *         'name' => 'reject-12',
 *         'title' => 'Reject the request?',
 *         'body' => $this->Form->create(null, ['url' => ['action' => 'reject', 12], 'id' => 'reject-12-form'])
 *             . $this->Form->control('reason') . $this->Form->end(),
 *         'footer' => '<button type="button" class="btn btn-outline-secondary" data-kk-modal-close>Cancel</button>'
 *             . '<button type="submit" form="reject-12-form" class="btn btn-danger">Reject</button>',
 *     ]) ?>
 *     <button type="button" class="btn btn-outline-danger" data-kk-modal-open="reject-12">Reject</button>
 *
 * @var \Cake\View\View $this
 * @var string $name The name openers use.
 * @var string $title The header's title.
 * @var string $body The body's markup, escaped by the caller.
 * @var string|null $footer The footer's markup (its buttons), escaped by the caller.
 * @var string|null $size `sm`, `md` (the default), `lg` or `xl`.
 * @var bool|null $isClosable False: no close button, and Esc and the backdrop do nothing.
 */
$this->loadHelper('RockSoftware/Ui.Ui');
$this->Ui->requireComponent('modal');
$footer = (string)($footer ?? '');
$attributes = sprintf(
    'data-kk-modal="%s" data-title="%s" data-size="%s" data-closable="%s"',
    h((string)$name),
    h((string)$title),
    h((string)($size ?? 'md')),
    ($isClosable ?? true) === false ? 'false' : 'true',
);
$footerHtml = $footer === '' ? '' : '<div data-kk-modal-footer>' . $footer . '</div>';
?>
<div <?= $attributes ?> hidden><?= (string)$body . $footerHtml ?></div>
