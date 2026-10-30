<?php
/**
 * An expand toggle: the row across the bottom of a compact section that
 * shows and hides the rest of it. A thin line with a centred label, "Show
 * more" with a down chevron when closed and "Show less" with an up chevron
 * when open; the whole row is one button.
 *
 *     <div id="provider-7-more" hidden>…</div>
 *     <?= $this->element('ui/expand_toggle', ['target' => 'provider-7-more']) ?>
 *
 * `kk-expand-toggle.js` (loaded by the layout once this element renders)
 * toggles the button's `aria-expanded`, its label and chevron and the
 * target's `hidden`, and dispatches a bubbling `kk:expand-toggle` event with
 * `{ expanded }` on the button. The target is rendered `hidden` when the
 * toggle starts closed; the script also sets it from `expanded` on load.
 *
 * @var \Cake\View\View $this
 * @var string $target The id of the element it shows and hides.
 * @var bool|null $expanded Whether the target starts open; default false.
 * @var string|null $showLabel The label while closed; default "Show more".
 * @var string|null $hideLabel The label while open; default "Show less".
 */
$this->loadHelper('RockSoftware/Ui.Ui');
if (!isset($target) || !is_string($target) || $target === '') {
    throw new InvalidArgumentException('The expand toggle needs the id of its target.');
}
$this->Ui->requireComponent('expandToggle');
$isExpanded = !empty($expanded);
$showLabel = isset($showLabel) && is_string($showLabel) && $showLabel !== ''
    ? $showLabel
    : __d('rock_software_ui', 'Show more');
$hideLabel = isset($hideLabel) && is_string($hideLabel) && $hideLabel !== ''
    ? $hideLabel
    : __d('rock_software_ui', 'Show less');
?>
<button type="button" class="kk-expand-toggle" data-kk-expand-toggle aria-controls="<?= h($target) ?>"
        aria-expanded="<?= $isExpanded ? 'true' : 'false' ?>"
        data-show-label="<?= h($showLabel) ?>" data-hide-label="<?= h($hideLabel) ?>">
    <span class="kk-expand-toggle__line" aria-hidden="true"></span>
    <span class="kk-expand-toggle__label"><span data-kk-expand-toggle-text><?=
        h($isExpanded ? $hideLabel : $showLabel)
    ?></span>
        <i class="bi <?= $isExpanded ? 'bi-chevron-up' : 'bi-chevron-down' ?>" aria-hidden="true"></i></span>
    <span class="kk-expand-toggle__line" aria-hidden="true"></span>
</button>
