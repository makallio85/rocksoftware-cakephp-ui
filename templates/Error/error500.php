<?php
/**
 * A 5xx error in the frame's `error` layout: a short title and text, in the
 * page's language (`UiHelper::errorText()`); the exception's message is not
 * shown. In debug mode, CakePHP's developer page as an application template
 * shows it.
 *
 * The theme's templates come before the application's, so this one is used
 * in place of an application's own `templates/Error/error500.php`.
 *
 * @var \Cake\View\View $this
 * @var string $message
 * @var \Throwable $error
 * @var int|null $code
 */
use Cake\Core\Configure;
use Cake\Error\Debugger;

if (Configure::read('debug')) :
    $this->setLayout('dev_error');

    $this->assign('title', $message);
    $this->assign('templateName', 'error500.php');

    $this->start('file');
    if ($error instanceof Error) :
        $file = $error->getFile();
        $line = $error->getLine();
        ?>
<strong>Error in: </strong>
        <?= $this->Html->link(
            sprintf('%s, line %s', Debugger::trimPath($file), $line),
            Debugger::editorUrl($file, $line),
        ) ?>
        <?php
    endif;
    echo $this->element('auto_table_warning');
    $this->end();

    // The developer page shows the title, the file and the trace, not the content.
    return;
endif;

$this->loadHelper('RockSoftware/Ui.Ui');
$this->setLayout('error');
$status = (int)($code ?? 500);
$texts = $this->Ui->errorText($status);
$this->assign('title', $texts['title']);
?>
<p class="text-body-secondary small mb-1"><?= h(__d('rock_software_ui', 'Error {0}', $status)) ?></p>
<h1 class="h5"><?= h($texts['title']) ?></h1>
<p class="text-body-secondary mb-0"><?= h($texts['text']) ?></p>
