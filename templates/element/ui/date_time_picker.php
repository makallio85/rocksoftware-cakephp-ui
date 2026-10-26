<?php
/**
 * A form field with `<kk-date-time-picker>`, for a page with no Vue app of
 * its own: the label, a text field the picker takes over (it posts the
 * value as before), the help text and the validation error. The field shows
 * DD.MM.YYYY HH.mm and posts wall-clock time in the display time zone
 * (`Y-m-d H:i`, `Y-m-d`, `H:i` or `Y-m`); read it back with
 * `new DateTime($posted, $this->Ui->displayTimezone())`. Rendering one loads
 * the `dateTimePicker` component on the page; the page needs Vue (the
 * application's, or the `vue` component).
 *
 *     <?= $this->element('ui/date_time_picker', [
 *         'name' => 'send_after',
 *         'label' => 'Send after',
 *         'value' => $message->send_after,
 *         'help' => 'Helsinki time.',
 *     ]) ?>
 *
 * @var \Cake\View\View $this
 * @var string $name The field's name.
 * @var mixed $value A day or a moment (`Date`, `DateTimeInterface`, a Unix timestamp), turned
 *   into wall-clock time in the display time zone, or a string that is already a picker value
 *   (the posted one); null for none.
 * @var string|null $mode `datetime` (the default), `date`, `time` or `month`.
 * @var string|null $label The label; none when null.
 * @var string|null $id The field's id; from the name by default.
 * @var string|null $help A help text under the field.
 * @var string|null $error A validation error under the field.
 * @var int|null $timeStep Minutes between the time list's times (30).
 * @var string|null $min The first day that may be picked, `Y-m-d`.
 * @var string|null $max The last day that may be picked, `Y-m-d`.
 * @var string|null $size `sm` or `lg`.
 * @var string|null $placeholder What the empty field says.
 * @var bool|null $isRequired Whether the field must be filled (no clear button).
 * @var bool|null $isDisabled Whether the field is disabled.
 */
$this->loadHelper('RockSoftware/Ui.Ui');
$this->Ui->requireComponent('dateTimePicker');
$mode = (string)($mode ?? 'datetime');
$id = (string)($id ?? trim((string)preg_replace('/[^a-z0-9]+/', '-', strtolower((string)$name)), '-'));
$help = (string)($help ?? '');
$error = (string)($error ?? '');
$describedBy = trim(($help !== '' ? $id . '-help' : '') . ' ' . ($error !== '' ? $id . '-error' : ''));
$attributes = [
    'type' => 'text',
    'name' => (string)$name,
    'id' => $id,
    // A string is a picker value already (such as the posted one, shown again
    // with a validation error); a day or a moment is turned into one.
    'value' => is_string($value ?? null) ? (string)$value : $this->Ui->pickerValue($value ?? null, $mode),
    'class' => 'form-control' . ($error !== '' ? ' is-invalid' : ''),
    'data-kk-date-time-picker' => true,
    'data-mode' => $mode,
    'data-time-step' => isset($timeStep) ? (string)(int)$timeStep : null,
    'data-min' => $min ?? null,
    'data-max' => $max ?? null,
    'data-size' => $size ?? null,
    'placeholder' => $placeholder ?? null,
    'aria-describedby' => $describedBy !== '' ? $describedBy : null,
    'required' => ($isRequired ?? false) === true,
    'disabled' => ($isDisabled ?? false) === true,
    'autocomplete' => 'off',
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
    <?php if ($error !== '') : ?>
    <div class="invalid-feedback d-block" id="<?= h($id) ?>-error"><?= h($error) ?></div>
    <?php endif; ?>
    <?php if ($help !== '') : ?>
    <div class="form-text" id="<?= h($id) ?>-help"><?= h($help) ?></div>
    <?php endif; ?>
</div>
