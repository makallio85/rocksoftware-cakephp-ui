<?php
/**
 * A file field with `<kk-file-drop>`, for a page with no Vue app of its own:
 * the label and a file input the drop target takes over. The input stays in
 * the form and holds the files chosen, so the form posts them as before (it
 * needs `'type' => 'file'`). Rendering one loads the `fileDrop` component on
 * the page; the page needs Vue (the application's, or the `vue` component).
 *
 *     <?= $this->element('ui/file_drop', [
 *         'name' => 'attachment',
 *         'label' => 'Attachment',
 *         'accept' => '.pdf,image/*',
 *         'maxBytes' => 10 * 1024 * 1024,
 *         'hint' => 'PDF or an image, at most 10 MB.',
 *     ]) ?>
 *
 * @var \Cake\View\View $this
 * @var string $name The field's name; `[]` is added for several files.
 * @var string|null $label The label; none when null.
 * @var string|null $id The field's id; from the name by default.
 * @var string|null $accept MIME types, wildcards and extensions (`image/*,.pdf`).
 * @var bool|null $isMultiple Whether several files may be chosen.
 * @var int|null $maxBytes The largest file taken; none by default.
 * @var string|null $text The line inside the target.
 * @var string|null $hint The help line under it.
 * @var bool|null $isDisabled Whether the field is disabled.
 */
$this->loadHelper('RockSoftware/Ui.Ui');
$this->Ui->requireComponent('fileDrop');
$isMultiple = ($isMultiple ?? false) === true;
$id = (string)($id ?? trim((string)preg_replace('/[^a-z0-9]+/', '-', strtolower((string)$name)), '-'));
$attributes = [
    'type' => 'file',
    'name' => (string)$name . ($isMultiple ? '[]' : ''),
    'id' => $id,
    'accept' => $accept ?? null,
    'multiple' => $isMultiple,
    'disabled' => ($isDisabled ?? false) === true,
    'data-kk-file-drop' => true,
    'data-max-bytes' => isset($maxBytes) ? (string)(int)$maxBytes : null,
    'data-text' => $text ?? null,
    'data-hint' => $hint ?? null,
    'class' => 'form-control',
];
$html = '';
foreach ($attributes as $attribute => $setting) {
    if ($setting === null || $setting === false) {
        continue;
    }
    $html .= ' ' . $attribute . ($setting === true ? '' : '="' . h((string)$setting) . '"');
}
?>
<div class="kk-field">
    <?php if (isset($label) && (string)$label !== '') : ?>
    <label class="form-label" for="<?= h($id) ?>"><?= h((string)$label) ?></label>
    <?php endif; ?>
    <input<?= $html ?>>
</div>
