<?php
/**
 * Top bar: hamburger and brand (or, with the breadcrumb on, the breadcrumb)
 * on the left, the user menu on the right.
 *
 * The signed-in person, the other applications and sign-out come from the
 * sign-in plugin (`UiHelper::userMenuItems()`); the frame only gives them
 * their look.
 *
 * @var \Cake\View\View $this
 */
$this->loadHelper('RockSoftware/Ui.Ui');
$menuItems = $this->Ui->userMenuItems();
?>
<header class="kk-topbar">
    <button class="kk-icon-btn kk-hamburger" type="button"
            data-bs-toggle="offcanvas" data-bs-target="#mainMenu"
            aria-controls="mainMenu" aria-label="<?= h(__d('rock_software_ui', 'Open menu')) ?>">
        <i class="bi bi-list"></i>
    </button>

    <?php if ($this->Ui->isEnabled('breadcrumb')) : ?>
        <?= $this->element('breadcrumb') ?>
    <?php else : ?>
        <?= $this->Html->link(
            '<span class="kk-brand-mark"><i class="' . h($this->Ui->brandIcon()) . '"></i></span>'
            . '<span class="kk-brand-name">' . h($this->Ui->appName()) . '</span>',
            $this->Ui->homeUrl(),
            ['class' => 'kk-brand', 'escapeTitle' => false],
        ) ?>
    <?php endif; ?>

    <?php if ($menuItems !== []) : ?>
    <div class="kk-topbar-right">
        <div class="dropdown">
            <button class="kk-user-btn" type="button" id="kkUserMenu"
                    data-bs-toggle="dropdown" aria-expanded="false"
                    aria-label="<?= h(__d('rock_software_ui', 'User menu')) ?>">
                <span class="kk-user-avatar"><i class="bi bi-person-fill"></i></span>
                <i class="bi bi-chevron-down kk-user-caret"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow kk-user-dropdown" aria-labelledby="kkUserMenu">
                <?php $appsShown = false; ?>
                <?php foreach ($menuItems as $item) : ?>
                    <?php if ($item['type'] === 'header') : ?>
                <li>
                    <div class="kk-user-name text-truncate"><?= h($item['label']) ?></div>
                    <div class="kk-user-email text-truncate"><?= h($item['detail']) ?></div>
                </li>
                    <?php elseif ($item['type'] === 'app') : ?>
                        <?php if (!$appsShown) : ?>
                <li><hr class="dropdown-divider"></li>
                <li><h6 class="dropdown-header"><?= h(__d('rock_software_ui', 'Applications')) ?></h6></li>
                            <?php $appsShown = true; ?>
                        <?php endif; ?>
                <li>
                        <?php $current = !empty($item['current']) ? ' aria-current="page"' : ''; ?>
                    <a class="dropdown-item d-flex align-items-center gap-2"
                       href="<?= h($item['url']) ?>"<?= $current ?>>
                        <i class="bi bi-grid text-secondary"></i><?= h($item['label']) ?>
                    </a>
                </li>
                    <?php elseif ($item['type'] === 'link') : ?>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <a class="dropdown-item d-flex align-items-center gap-2" href="<?= h($item['url']) ?>">
                        <i class="bi bi-person-badge text-secondary"></i><?= h($item['label']) ?>
                    </a>
                </li>
                    <?php elseif ($item['type'] === 'sign_out') : ?>
                <li>
                        <?= $this->Form->postLink(
                            '<i class="bi bi-box-arrow-right text-secondary"></i>' . h($item['label']),
                            $item['url'],
                            ['class' => 'dropdown-item d-flex align-items-center gap-2', 'escapeTitle' => false],
                        ) ?>
                </li>
                    <?php endif; ?>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
    <?php endif; ?>
</header>
