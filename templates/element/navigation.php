<?php
/**
 * The main menu, from the `mainMenu` view variable (a `MainMenuInterface`).
 * On desktop a group's items open as a hover flyout beside the drawer; on
 * phones a tap on the group expands it in place. A group with a `url` and
 * no items is one direct link, with the group's look and no flyout.
 *
 * @var \Cake\View\View $this
 */
$this->loadHelper('RockSoftware/Ui.Ui');
?>
<nav class="kk-nav">
    <?php foreach ($this->Ui->menuGroups() as $group) : ?>
        <?php if ($this->Ui->isLinkGroup($group)) : ?>
            <?php $active = $this->Ui->isActive($group); ?>
    <div class="kk-group kk-group-link">
            <?= $this->Html->link(
                '<i class="group-icon ' . h($group['icon']) . '"></i>'
                . '<span class="group-title">' . h($group['title']) . '</span>',
                $group['url'],
                [
                    'class' => 'group-btn' . ($active ? ' active' : ''),
                    'aria-current' => $active ? 'page' : null,
                    'escapeTitle' => false,
                ],
            ) ?>
    </div>
            <?php continue; ?>
        <?php endif; ?>
    <div class="kk-group">
        <button class="group-btn" type="button">
            <i class="group-icon <?= h($group['icon']) ?>"></i>
            <span class="group-title"><?= h($group['title']) ?></span>
            <i class="bi bi-chevron-down group-caret"></i>
        </button>
        <div class="sub">
            <?php foreach ($group['items'] ?? [] as $item) : ?>
                <?php $active = $this->Ui->isActive($item); ?>
                <?php $count = $this->Ui->badgeCount($item); ?>
                <?= $this->Html->link(
                    h($item['title']) . ($count > 0
                        ? ' <span class="badge rounded-pill bg-danger text-white ms-1"'
                            . ' data-menu-badge="' . h($item['badge'] ?? '') . '">' . $count
                            . (isset($item['badgeLabel'])
                                ? '<span class="visually-hidden"> ' . h($item['badgeLabel']) . '</span>'
                                : '')
                            . '</span>'
                        : ''),
                    $item['url'],
                    [
                        'class' => $active ? 'active' : null,
                        'aria-current' => $active ? 'page' : null,
                        'escapeTitle' => false,
                    ],
                ) ?>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endforeach; ?>
</nav>
