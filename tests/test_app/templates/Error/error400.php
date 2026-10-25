<?php
/**
 * @var \Cake\View\View $this
 * @var string $message
 */
$this->setLayout('error');
$this->assign('title', 'Error');
?>
<h1 class="h5">Something went wrong</h1>
<p class="text-body-secondary mb-0"><?= h($message) ?></p>
