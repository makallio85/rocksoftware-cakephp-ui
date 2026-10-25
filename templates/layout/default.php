<?php
/**
 * The signed-in layout.
 *
 * A fixed white top bar (hamburger, brand, user menu), a light off-canvas
 * drawer holding the main menu (hover flyouts on desktop, an accordion on
 * phones, a pin that docks it open), and the page content.
 *
 * Blocks: `title`, `meta`, `css`, `banner` (under the top bar), `content`,
 * `script`. View variables: `mainMenu` (a `MainMenuInterface`), optionally
 * `userMenuItems` and, with the breadcrumb on, `breadcrumb`.
 *
 * @var \Cake\View\View $this
 */
$this->loadHelper('RockSoftware/Ui.Ui');
$banner = $this->fetch('banner') . $this->Ui->elements('default', 'banner');
// The optional components' texts, which their scripts read from this block.
$componentTexts = $this->Ui->componentTexts() === [] ? '' : json_encode(
    $this->Ui->componentTexts(),
    JSON_THROW_ON_ERROR | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT,
);
?>
<!DOCTYPE html>
<html lang="<?= h($this->Ui->htmlLang()) ?>">
<head>
    <?= $this->Html->charset() ?>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= h($this->getRequest()->getAttribute('csrfToken')) ?>">
    <title><?= h($this->Ui->title($this->fetch('title'))) ?></title>
    <?= $this->Html->meta('icon') ?>
    <?= $this->Html->css($this->Ui->css()) ?>
    <?= $this->Ui->elements('default', 'head') ?>
    <?= $this->fetch('meta') ?>
    <?= $this->fetch('css') ?>
</head>
<body class="<?= h($this->Ui->bodyClass('kk-app')) ?>">

<?= $this->element('topbar') ?>

<div id="app-scroll">
    <?php if ($banner !== '') : ?>
    <div class="kk-banner"><?= $banner ?></div>
    <?php endif; ?>
    <div class="p-3">
        <?= $this->Flash->render() ?>
        <?= $this->fetch('content') ?>
    </div>
</div>

<div class="offcanvas offcanvas-start" tabindex="-1" id="mainMenu"
     aria-label="<?= h(__d('rock_software_ui', 'Main menu')) ?>"
     data-bs-scroll="true" data-bs-backdrop="true"
     data-storage-key-prefix="<?= h($this->Ui->storageKeyPrefix()) ?>">
    <div class="offcanvas-header">
        <button type="button" class="kk-icon-btn kk-menu-pin" id="kkMenuPin"
                aria-pressed="false" title="<?= h(__d('rock_software_ui', 'Pin menu')) ?>"
                aria-label="<?= h(__d('rock_software_ui', 'Pin menu')) ?>"
                data-label-pin="<?= h(__d('rock_software_ui', 'Pin menu')) ?>"
                data-label-unpin="<?= h(__d('rock_software_ui', 'Unpin menu')) ?>">
            <i class="bi bi-pin-angle"></i>
        </button>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"
                aria-label="<?= h(__d('rock_software_ui', 'Close menu')) ?>"></button>
    </div>
    <div class="offcanvas-body">
        <?= $this->element('navigation') ?>
    </div>
</div>

<?php if ($componentTexts !== '') : ?>
<script type="application/json" id="kk-ui-texts"><?= $componentTexts ?></script>
<?php endif; ?>
<?= $this->Html->script($this->Ui->scripts()) ?>
<?= $this->Ui->elements('default', 'bodyEnd') ?>
<?= $this->fetch('script') ?>
</body>
</html>
