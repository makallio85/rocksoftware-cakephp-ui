<?php
/**
 * The sign-in layout: no navigation, reachable without a session.
 *
 * Blocks: `title`, `css`, `content`, `script`.
 *
 * @var \Cake\View\View $this
 */
$this->loadHelper('RockSoftware/Ui.Ui');
$organizationName = $this->Ui->organizationName();
?>
<!DOCTYPE html>
<html lang="<?= h($this->Ui->htmlLang()) ?>">
<head>
    <?= $this->Html->charset() ?>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= h($this->Ui->title($this->fetch('title'))) ?></title>
    <?= $this->Html->meta('icon') ?>
    <?= $this->Html->css($this->Ui->css(false)) ?>
    <?= $this->Ui->elements('auth', 'head') ?>
    <?= $this->fetch('css') ?>
</head>
<body class="<?= h($this->Ui->bodyClass('kk-auth')) ?>">
<main class="auth-shell">
    <div class="auth-card">
        <div class="text-center mb-4">
            <div class="auth-mark"><i class="<?= h($this->Ui->brandIcon()) ?>"></i></div>
            <h1 class="h5 mt-3 mb-1"><?= h($this->Ui->appName()) ?></h1>
            <?php if ($organizationName !== null) : ?>
            <p class="text-body-secondary small mb-0"><?= h($organizationName) ?></p>
            <?php endif; ?>
        </div>
        <?= $this->Flash->render() ?>
        <?= $this->fetch('content') ?>
    </div>
</main>
<?= $this->Html->script('RockSoftware/Ui.bootstrap.bundle.min') ?>
<?= $this->Ui->elements('auth', 'bodyEnd') ?>
<?= $this->fetch('script') ?>
</body>
</html>
