<?php
/**
 * A kebab menu (⋮): the actions on a row or a page, as a Bootstrap dropdown
 * aligned to the right. Nothing renders when no item is left.
 *
 *     <?= $this->element('ui/kebab', ['items' => [
 *         ['title' => 'Edit', 'url' => ['action' => 'edit', 7], 'icon' => 'bi bi-pencil'],
 *         ['type' => 'divider'],
 *         ['type' => 'post', 'title' => 'Delete', 'url' => ['action' => 'delete', 7],
 *          'confirm' => 'Delete this provider?', 'isDestructive' => true],
 *     ]]) ?>
 *
 *     ['type' => 'button', 'title' => 'Archive…', 'attributes' => ['data-kk-modal-open' => 'archive']]
 *
 * Items, by `type`:
 * - `link` (the default): `title`, `url`, optional `icon` (Bootstrap Icons
 *   class), `isDestructive`, `isDisabled`;
 * - `post`: a form posting to `url` (`UiHelper::postButton()`): `title`,
 *   `url`, optional `icon`, `data`, `method`, `confirm`, `confirmTitle`,
 *   `confirmButton`, `confirmTone`, `isDestructive`;
 * - `button`: a `<button type="button">` for a script to act on, such as
 *   opening a markup modal: `title`, `attributes` (its HTML attributes, e.g.
 *   `['data-kk-modal-open' => 'archive']`), optional `icon`, `isDestructive`,
 *   `isDisabled`;
 * - `header`: a heading, `title`;
 * - `text`: markup as it is, `html`, e.g. a switch (`form-check form-switch`);
 * - `divider`.
 *
 * @var \Cake\View\View $this
 * @var array<int, array<string, mixed>> $items The menu's items.
 * @var string|null $label The button's accessible name; default "Actions".
 * @var string|null $size `sm` (the default, for a row) or `md` (a page header).
 */
$this->loadHelper('RockSoftware/Ui.Ui');
$items = array_values(array_filter((array)($items ?? []), 'is_array'));
// A menu of nothing but dividers is no menu.
$hasAction = array_filter($items, fn(array $item): bool => ($item['type'] ?? 'link') !== 'divider');
if ($hasAction === []) {
    return;
}
$label = isset($label) && is_string($label) && $label !== '' ? $label : __d('rock_software_ui', 'Actions');
// A ghost button (kk-components.css); `md` is a little larger, for a page header.
$buttonClass = 'btn kk-kebab-toggle' . (($size ?? 'sm') === 'sm' ? '' : ' kk-kebab-toggle-md');
$icon = function (array $item): string {
    return isset($item['icon']) ? '<i class="' . h((string)$item['icon']) . '" aria-hidden="true"></i> ' : '';
};
$raw = ['escapeTitle' => false];
$postItem = function (array $item, string $title, string $class): string {
    $options = array_intersect_key(
        $item,
        array_flip(['data', 'method', 'confirm', 'confirmTitle', 'confirmButton', 'confirmTone']),
    );

    return $this->Ui->postButton(
        $title,
        $item['url'] ?? [],
        $options + ['class' => $class, 'escapeTitle' => false, 'form' => ['class' => 'm-0']],
    );
};
?>
<div class="dropdown kk-kebab">
    <button type="button" class="<?= h($buttonClass) ?>" data-bs-toggle="dropdown" aria-expanded="false"
            aria-label="<?= h($label) ?>" title="<?= h($label) ?>">
        <i class="bi bi-three-dots-vertical" aria-hidden="true"></i>
    </button>
    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
        <?php foreach ($items as $item) : ?>
            <?php
            $type = (string)($item['type'] ?? 'link');
            $itemClass = 'dropdown-item' . (!empty($item['isDestructive']) ? ' text-danger' : '');
            $itemTitle = $icon($item) . h((string)($item['title'] ?? ''));
            ?>
            <?php if ($type === 'divider') : ?>
        <li><hr class="dropdown-divider"></li>
            <?php elseif ($type === 'header') : ?>
        <li><h6 class="dropdown-header"><?= h((string)($item['title'] ?? '')) ?></h6></li>
            <?php elseif ($type === 'text') : ?>
        <li><div class="dropdown-item-text"><?= (string)($item['html'] ?? '') ?></div></li>
            <?php elseif ($type === 'button') : ?>
        <li><?= $this->Html->tag('button', $itemTitle, array_filter([
            'type' => 'button',
            'class' => $itemClass,
            'disabled' => !empty($item['isDisabled']),
        ]) + (array)($item['attributes'] ?? [])) ?></li>
            <?php elseif ($type === 'post') : ?>
        <li><?= $postItem($item, $itemTitle, $itemClass) ?></li>
            <?php elseif (!empty($item['isDisabled'])) : ?>
        <li><span class="<?= h($itemClass) ?> disabled" aria-disabled="true"><?= $itemTitle ?></span></li>
            <?php else : ?>
        <li><?= $this->Html->link($itemTitle, $item['url'] ?? '#', ['class' => $itemClass] + $raw) ?></li>
            <?php endif; ?>
        <?php endforeach; ?>
    </ul>
</div>
