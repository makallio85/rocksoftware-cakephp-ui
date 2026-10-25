<?php
/**
 * The error page's layout: no navigation and no user, a card with the error
 * and a way back.
 *
 * @var \Cake\View\View $this
 */
$this->loadHelper('RockSoftware/Ui.Ui');
?>
<!DOCTYPE html>
<html lang="<?= h($this->Ui->htmlLang()) ?>">
<head>
    <?= $this->Html->charset() ?>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= h($this->Ui->title($this->fetch('title'), false)) ?></title>
    <?= $this->Html->meta('icon') ?>
    <?= $this->Html->css($this->Ui->css(false)) ?>
    <?= $this->Ui->elements('error', 'head') ?>
</head>
<body class="<?= h($this->Ui->bodyClass('kk-auth')) ?>">
<main class="auth-shell">
    <div class="auth-card">
        <?= $this->fetch('content') ?>
        <div class="mt-4">
            <?= $this->Html->link(
                '<i class="bi bi-arrow-left me-1"></i>' . h(__d('rock_software_ui', 'Back to the dashboard')),
                $this->Ui->homeUrl(),
                [
                    'class' => 'btn btn-outline-secondary btn-sm',
                    'escapeTitle' => false,
                ],
            ) ?>
        </div>
    </div>
</main>
<?= $this->Ui->elements('error', 'bodyEnd') ?>
</body>
</html>
