<?php
/**
 * A 4xx error (404, 403, …) in the frame's `error` layout: a short title and
 * text by status, in the page's language (`UiHelper::errorText()`). In debug
 * mode, CakePHP's developer page as an application template shows it.
 *
 * The theme's templates come before the application's, so this one is used
 * in place of an application's own `templates/Error/error400.php`.
 *
 * @var \Cake\View\View $this
 * @var string $message
 * @var int|null $code
 */
use Cake\Core\Configure;

if (Configure::read('debug')) :
    $this->setLayout('dev_error');

    $this->assign('title', $message);
    $this->assign('templateName', 'error400.php');

    $this->start('file');
    echo $this->element('auto_table_warning');
    $this->end();

    // The developer page shows the title, the file and the trace, not the content.
    return;
endif;

$this->loadHelper('RockSoftware/Ui.Ui');
$this->setLayout('error');
$status = (int)($code ?? 400);
$texts = $this->Ui->errorText($status);
$this->assign('title', $texts['title']);
?>
<p class="text-body-secondary small mb-1"><?= h(__d('rock_software_ui', 'Error {0}', $status)) ?></p>
<h1 class="h5"><?= h($texts['title']) ?></h1>
<p class="text-body-secondary mb-0"><?= h($texts['text']) ?></p>
