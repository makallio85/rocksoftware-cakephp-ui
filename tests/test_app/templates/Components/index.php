<?php
/**
 * Every shared in-page component, with fictional data.
 *
 * @var \Cake\View\View $this
 * @var \Cake\Datasource\Paging\PaginatedResultSet<array-key, mixed> $messages
 * @var \TestApp\Form\DemoSettingsForm $settingsForm
 */
$this->assign('title', 'Components');
// The toasts are used on this page only, so the page asks for them.
$this->Ui->requireComponent('toast');
$this->Html->script('demo', ['block' => true]);
$this->Paginator->setTemplates(\RockSoftware\Ui\View\Helper\UiHelper::PAGINATOR_TEMPLATES);

$rowMenu = fn(array $row): array => [
    ['title' => 'Open', 'url' => '#message-' . $row['id'], 'icon' => 'bi bi-box-arrow-up-right'],
    ['title' => 'Resend', 'url' => '#resend-' . $row['id'], 'icon' => 'bi bi-arrow-repeat', 'isDisabled' => $row['tone'] !== 'danger'],
    ['type' => 'divider'],
    [
        'type' => 'post',
        'title' => 'Delete',
        'icon' => 'bi bi-trash',
        'url' => ['action' => 'delete', $row['id']],
        'confirm' => 'Delete the message to ' . $row['recipient'] . '? This cannot be undone.',
        'confirmTitle' => 'Delete this message?',
        'confirmButton' => 'Delete',
        'isDestructive' => true,
    ],
];
?>
<?= $this->element('ui/page_header', [
    'title' => 'Messages',
    'subtitle' => 'Page header element: title, optional subtitle, actions and a kebab menu.',
    'actions' => [
        ['title' => 'New message', 'url' => '#new', 'icon' => 'bi bi-plus-lg', 'isPrimary' => true],
    ],
    'menu' => [
        ['type' => 'header', 'title' => 'View'],
        ['type' => 'text', 'html' => '<div class="form-check form-switch m-0">'
            . '<input class="form-check-input" type="checkbox" role="switch" id="demo-show-failed" checked>'
            . '<label class="form-check-label" for="demo-show-failed">Show failed</label></div>'],
        ['type' => 'divider'],
        ['title' => 'Export as CSV', 'url' => '#export', 'icon' => 'bi bi-download'],
        ['type' => 'divider'],
        [
            'type' => 'post',
            'title' => 'Delete all',
            'icon' => 'bi bi-trash',
            'url' => ['action' => 'delete', 'all'],
            'confirm' => 'Every message in the list is deleted. This cannot be undone.',
            'confirmTitle' => 'Delete all messages?',
            'confirmButton' => 'Delete all',
            'isDestructive' => true,
        ],
    ],
]) ?>

<div class="card mb-3">
    <form method="get" class="card-header bg-white d-flex flex-wrap gap-3 align-items-end">
        <div>
            <label class="form-label small mb-1" for="demo-search">Search</label>
            <input class="form-control" id="demo-search" name="q" placeholder="Recipient or reference">
        </div>
        <div>
            <label class="form-label small mb-1" for="demo-status">Status</label>
            <select class="form-select" id="demo-status" name="status"><option>All statuses</option></select>
        </div>
        <span class="ms-md-auto text-body-secondary small"><?= h($this->Ui->number($messages->totalCount())) ?> messages</span>
    </form>
    <div class="table-responsive d-none d-md-block">
        <table class="table table-hover align-middle mb-0">
            <thead>
            <tr>
                <th scope="col"><?= $this->Paginator->sort('sent_at', 'Sent') ?></th>
                <th scope="col">Recipient</th>
                <th scope="col">Application</th>
                <th scope="col">Channel</th>
                <th scope="col">Status</th>
                <th scope="col" class="text-end"><span class="visually-hidden">Actions</span></th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($messages as $row) : ?>
            <tr>
                <td class="text-nowrap"><?= h($this->Ui->dateTime($row['sent_at'])) ?></td>
                <td><?= h($row['recipient']) ?></td>
                <td><?= h($row['application']) ?></td>
                <td><?= h($row['channel']) ?></td>
                <td><?= $this->Ui->badge($row['status'], $row['tone']) ?></td>
                <td class="text-end"><?= $this->element('ui/kebab', ['items' => $rowMenu($row)]) ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <ul class="list-group list-group-flush d-md-none">
        <?php foreach ($messages as $row) : ?>
        <li class="list-group-item d-flex gap-2 align-items-start">
            <div class="flex-grow-1 min-w-0">
                <div class="d-flex justify-content-between gap-2">
                    <strong class="text-break"><?= h($row['recipient']) ?></strong>
                    <?= $this->Ui->badge($row['status'], $row['tone']) ?>
                </div>
                <div class="text-body-secondary small">
                    <?= h($this->Ui->dateTime($row['sent_at'])) ?> · <?= h($row['channel']) ?> · <?= h($row['application']) ?>
                </div>
            </div>
            <?= $this->element('ui/kebab', ['items' => $rowMenu($row)]) ?>
        </li>
        <?php endforeach; ?>
    </ul>
    <div class="card-footer bg-white">
        <?= $this->element('ui/pagination') ?>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-12 col-lg-4">
        <section class="card h-100"><div class="card-body">
            <h2 class="h6 text-uppercase text-body-secondary small fw-bold">Badges</h2>
            <div class="d-flex flex-wrap gap-2 mb-3" id="demo-badges">
                <?php foreach (\RockSoftware\Ui\View\Helper\UiHelper::BADGE_TONES as $tone) : ?>
                    <?= $this->Ui->badge($tone, $tone) ?>
                <?php endforeach; ?>
            </div>
            <h2 class="h6 text-uppercase text-body-secondary small fw-bold">Buttons</h2>
            <div class="d-flex flex-wrap gap-2 align-items-center">
                <button type="button" class="btn btn-primary">Primary</button>
                <button type="button" class="btn btn-outline-secondary">Secondary</button>
                <?= $this->Ui->postButton('Delete…', ['action' => 'delete', 'provider'], [
                    'class' => 'btn btn-outline-danger',
                    'confirm' => 'Messages already sent stay in the log. This cannot be undone.',
                    'confirmTitle' => 'Delete this provider?',
                    'confirmButton' => 'Delete',
                ]) ?>
                <?= $this->element('ui/kebab', ['items' => [['title' => 'Duplicate', 'url' => '#duplicate']], 'size' => 'md']) ?>
            </div>
            <h2 class="h6 text-uppercase text-body-secondary small fw-bold mt-3">Formats</h2>
            <dl class="row small mb-0" id="demo-formats">
                <dt class="col-5 fw-normal text-body-secondary">date()</dt><dd class="col-7"><?= h($this->Ui->date('2026-10-26')) ?></dd>
                <dt class="col-5 fw-normal text-body-secondary">dateTime()</dt><dd class="col-7"><?= h($this->Ui->dateTime('2026-10-26T06:14:00Z')) ?></dd>
                <dt class="col-5 fw-normal text-body-secondary">number()</dt><dd class="col-7"><?= h($this->Ui->number(1842.3, 1)) ?></dd>
                <dt class="col-5 fw-normal text-body-secondary">money()</dt><dd class="col-7 mb-0"><?= h($this->Ui->money('1842.30')) ?></dd>
            </dl>
        </div></section>
    </div>
    <div class="col-12 col-lg-4">
        <section class="card h-100"><div class="card-body">
            <h2 class="h6 text-uppercase text-body-secondary small fw-bold">Confirm before a destructive action</h2>
            <p class="small text-body-secondary">A post button with a question opens the dialog; it works without inline script.</p>
            <div class="d-flex flex-wrap gap-2">
                <?= $this->Ui->postButton('<i class="bi bi-trash" aria-hidden="true"></i> Delete provider', ['action' => 'delete', 'provider'], [
                    'class' => 'btn btn-outline-danger',
                    'escapeTitle' => false,
                    'id' => 'demo-confirm-delete',
                    'confirm' => 'Messages already sent stay in the log. This cannot be undone.',
                    'confirmTitle' => 'Delete this provider?',
                    'confirmButton' => 'Delete',
                ]) ?>
                <button type="button" class="btn btn-outline-secondary" data-demo-ask>Ask from a script</button>
            </div>
        </div></section>
    </div>
    <div class="col-12 col-lg-4">
        <section class="card h-100"><div class="card-body">
            <h2 class="h6 text-uppercase text-body-secondary small fw-bold">Toast messages</h2>
            <div class="d-flex flex-wrap gap-2 mb-3">
                <button type="button" class="btn btn-sm btn-outline-secondary" data-demo-toast="success">Success</button>
                <button type="button" class="btn btn-sm btn-outline-secondary" data-demo-toast="error">Error</button>
                <button type="button" class="btn btn-sm btn-outline-secondary" data-demo-toast="warning">Warning</button>
                <button type="button" class="btn btn-sm btn-outline-secondary" data-demo-toast="info">Info</button>
            </div>
            <h2 class="h6 text-uppercase text-body-secondary small fw-bold">Empty state</h2>
            <?= $this->element('ui/empty_state', [
                'title' => 'No messages yet',
                'text' => 'Messages appear here once an application sends one.',
            ]) ?>
        </div></section>
    </div>
</div>

<section class="card mb-3 kk-page-narrow">
    <div class="card-body">
        <h2 class="h6 text-uppercase text-body-secondary small fw-bold">Form: label, help text, validation</h2>
        <ul class="nav nav-tabs nav-tabs-scrollable mb-3" id="demo-tabs">
            <li class="nav-item"><a class="nav-link active" aria-current="page" href="#">Sender</a></li>
            <li class="nav-item"><a class="nav-link" href="#">Delivery windows</a></li>
            <li class="nav-item"><a class="nav-link" href="#">Rate limits</a></li>
            <li class="nav-item"><a class="nav-link" href="#">Bounce handling</a></li>
            <li class="nav-item"><a class="nav-link" href="#">Audit trail</a></li>
        </ul>
        <?= $this->Form->create($settingsForm, ['url' => '#']) ?>
        <div class="row g-3">
            <div class="col-12 col-md-6">
                <?= $this->Form->control('sender_name', [
                    'class' => 'form-control',
                    'label' => ['class' => 'form-label', 'text' => 'Sender name'],
                    'help' => 'Shown to the recipient as the sender.',
                ]) ?>
            </div>
            <div class="col-12 col-md-6">
                <?= $this->Form->control('send_after', [
                    'class' => 'form-control',
                    'label' => ['class' => 'form-label', 'text' => 'Send after'],
                    'help' => 'DD.MM.YYYY HH.mm, Helsinki time.',
                ]) ?>
            </div>
            <div class="col-12 col-md-6">
                <?= $this->Form->control('reply_address', [
                    'class' => 'form-control',
                    'label' => ['class' => 'form-label', 'text' => 'Reply address'],
                    'help' => 'Replies from recipients go here.',
                ]) ?>
            </div>
            <div class="col-12 col-md-6 d-flex align-items-end">
                <div class="form-check form-switch mb-2">
                    <?= $this->Form->checkbox('is_sending_enabled', [
                        'class' => 'form-check-input', 'role' => 'switch', 'id' => 'is-sending-enabled', 'hiddenField' => false,
                    ]) ?>
                    <label class="form-check-label" for="is-sending-enabled">Is sending enabled</label>
                </div>
            </div>
        </div>
        <div class="d-flex gap-2 mt-3">
            <button type="button" class="btn btn-primary">Save</button>
            <button type="button" class="btn btn-outline-secondary">Cancel</button>
        </div>
        <?= $this->Form->end() ?>
    </div>
</section>
