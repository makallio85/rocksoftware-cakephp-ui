<?php
/**
 * The page header: the title, an optional subtitle and read-only badges, the
 * page's actions and an optional kebab menu, right-aligned. On phones the
 * actions with an icon show the icon alone.
 *
 *     <?= $this->element('ui/page_header', [
 *         'title' => 'Messages',
 *         'subtitle' => 'Everything sent in the last 90 days.',
 *         'actions' => [
 *             ['title' => 'New message', 'url' => ['action' => 'add'], 'icon' => 'bi bi-plus-lg', 'isPrimary' => true],
 *         ],
 *         'menu' => [['title' => 'Export', 'url' => ['action' => 'export']]],
 *     ]) ?>
 *
 * @var \Cake\View\View $this
 * @var string $title The page's title (the `<h1>`).
 * @var string|null $subtitle A line under the title.
 * @var string|null $badges Markup beside the title, such as `UiHelper::badge()`s.
 * @var array<int, array<string, mixed>>|string|null $actions Markup, or buttons:
 *   `title`, `url` (a link) or `type` `post` (`UiHelper::postButton()`, with
 *   `confirm` etc.) or `button` (a `<button type="button">` for a script),
 *   optional `icon`, `isPrimary`, `attributes` (more HTML attributes).
 * @var array<int, array<string, mixed>>|null $menu The kebab's items (`ui/kebab`).
 * @var string|null $menuLabel The kebab's accessible name; default "More actions".
 */
$this->loadHelper('RockSoftware/Ui.Ui');
$actionsHtml = '';
if (is_string($actions ?? null)) {
    $actionsHtml = $actions;
} else {
    foreach ((array)($actions ?? []) as $action) {
        if (!is_array($action)) {
            continue;
        }
        $actionTitle = (string)($action['title'] ?? '');
        $hasIcon = isset($action['icon']);
        $content = ($hasIcon ? '<i class="' . h((string)$action['icon']) . '" aria-hidden="true"></i>' : '')
            . '<span class="' . ($hasIcon ? 'kk-page-header-label' : '') . '">' . h($actionTitle) . '</span>';
        $attributes = (array)($action['attributes'] ?? []) + [
            'class' => 'btn ' . (!empty($action['isPrimary']) ? 'btn-primary' : 'btn-outline-secondary'),
            'escapeTitle' => false,
        ];
        if ($hasIcon) {
            $attributes += ['aria-label' => $actionTitle];
        }
        $type = (string)($action['type'] ?? 'link');
        if ($type === 'post') {
            $actionsHtml .= $this->Ui->postButton(
                $content,
                $action['url'] ?? [],
                $attributes + array_intersect_key(
                    $action,
                    array_flip(['data', 'method', 'confirm', 'confirmTitle', 'confirmButton', 'confirmTone']),
                ),
            );
        } elseif ($type === 'button') {
            unset($attributes['escapeTitle']);
            $actionsHtml .= $this->Html->tag('button', $content, ['type' => 'button'] + $attributes);
        } else {
            $actionsHtml .= $this->Html->link($content, $action['url'] ?? '#', $attributes);
        }
    }
}
$menuHtml = $this->element('ui/kebab', [
    'items' => (array)($menu ?? []),
    'label' => isset($menuLabel) && is_string($menuLabel) ? $menuLabel : __d('rock_software_ui', 'More actions'),
    'size' => 'md',
]);
?>
<div class="kk-page-header">
    <div class="kk-page-header-text">
        <h1 class="kk-page-header-title"><span><?= h((string)$title) ?></span><?= (string)($badges ?? '') ?></h1>
        <?php if (isset($subtitle) && (string)$subtitle !== '') : ?>
        <p class="kk-page-header-subtitle"><?= h((string)$subtitle) ?></p>
        <?php endif; ?>
    </div>
    <?php if ($actionsHtml !== '' || trim($menuHtml) !== '') : ?>
    <div class="kk-page-header-actions"><?= $actionsHtml ?><?= $menuHtml ?></div>
    <?php endif; ?>
</div>
