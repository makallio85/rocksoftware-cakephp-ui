<?php
/**
 * Every shared in-page component, with fictional data.
 *
 * @var \Cake\View\View $this
 * @var \Cake\Datasource\Paging\PaginatedResultSet<array-key, mixed> $messages
 * @var \TestApp\Form\DemoSettingsForm $settingsForm
 * @var array<string, string> $filterValues
 * @var \Cake\I18n\DateTime $sendAfter
 */
$this->assign('title', 'Components');
// The toasts and the formats are used on this page only, so the page asks
// for them; the test application has no Vue of its own, so it takes the
// plugin's runtime for the Vue components.
$this->Ui->requireComponent('toast');
$this->Ui->requireComponent('format');
$this->Ui->requireComponent('vue');
$this->Ui->requireComponent('listTable');
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

<section class="card mb-3" id="demo-list-table">
    <div class="card-body">
        <h2 class="h6 text-uppercase text-body-secondary small fw-bold">List table: server-paged, with filters and selection</h2>
        <form id="demo-bulk-form" method="post" action="<?= h($this->Url->build(['action' => 'delete', 'selected'])) ?>">
            <input type="hidden" name="_csrfToken" value="<?= h((string)$this->getRequest()->getAttribute('csrfToken')) ?>">
        </form>
        <?= $this->element('ui/list_table', [
            'id' => 'demo-messages',
            'results' => $messages,
            'columns' => [
                ['key' => 'sentAt', 'label' => 'Sent', 'sortField' => 'sent_at', 'class' => 'text-nowrap'],
                ['key' => 'recipient', 'label' => 'Recipient', 'sortable' => false],
                ['key' => 'application', 'label' => 'Application', 'sortable' => false],
                ['key' => 'channel', 'label' => 'Channel', 'sortable' => false],
                ['key' => 'status', 'label' => 'Status', 'sortable' => false, 'isHtml' => true],
            ],
            'rows' => array_map(fn(array $row): array => [
                'id' => $row['id'],
                'sentAt' => $this->Ui->dateTime($row['sent_at']),
                'recipient' => $row['recipient'],
                'application' => $row['application'],
                'channel' => $row['channel'],
                'status' => $this->Ui->badge($row['status'], $row['tone']),
                '_actions' => $this->element('ui/kebab', ['items' => $rowMenu($row)]),
            ], iterator_to_array($messages)),
            'filters' => [
                ['key' => 'q', 'label' => 'Search', 'type' => 'text', 'param' => 'q', 'placeholder' => 'Recipient'],
                ['key' => 'status', 'label' => 'Status', 'type' => 'select', 'param' => 'status', 'options' => [
                    ['value' => 'ok', 'label' => 'Delivered'],
                    ['value' => 'pending', 'label' => 'Queued'],
                    ['value' => 'danger', 'label' => 'Failed'],
                    ['value' => 'muted', 'label' => 'Not sent'],
                ]],
            ],
            'filterValues' => $filterValues,
            'selectable' => true,
            'selectionName' => 'ids',
            'selectionForm' => 'demo-bulk-form',
            'bulk' => '<span class="small text-body-secondary">Chosen messages:</span>'
                . '<button type="submit" form="demo-bulk-form" class="btn btn-sm btn-outline-danger"'
                . ' data-confirm="The chosen messages are deleted. This cannot be undone."'
                . ' data-confirm-title="Delete the chosen messages?" data-confirm-button="Delete">Delete</button>',
            'emptyText' => 'No messages match the filters.',
        ]) ?>
    </div>
</section>

<section class="card mb-3" id="demo-remembered">
    <div class="card-body">
        <h2 class="h6 text-uppercase text-body-secondary small fw-bold">List table: all rows on the page, filters remembered</h2>
        <p class="small text-body-secondary">The channel and the search are kept in this browser for this list (rememberFilters) and come back after a reload.</p>
        <?php
        $gateways = ['North', 'South', 'East', 'West', 'Harbour', 'Hill', 'River', 'Forest'];
        ?>
        <?= $this->element('ui/list_table', [
            'id' => 'demo-gateways',
            'columns' => [
                ['key' => 'name', 'label' => 'Provider'],
                ['key' => 'channel', 'label' => 'Channel'],
                ['key' => 'status', 'label' => 'Status', 'sortable' => false, 'isHtml' => true],
            ],
            'rows' => array_map(fn(int $i): array => [
                'id' => $i + 1,
                'name' => $gateways[$i] . ' Example Gateway',
                'channel' => $i % 3 === 0 ? 'SMS' : 'Email',
                'status' => $i % 4 === 3 ? $this->Ui->badge('Paused', 'muted') : $this->Ui->badge('Active', 'ok'),
            ], array_keys($gateways)),
            'filters' => [
                ['key' => 'q', 'label' => 'Search', 'type' => 'text', 'placeholder' => 'Provider'],
                ['key' => 'channel', 'label' => 'Channel', 'type' => 'select', 'matchField' => 'channel', 'options' => [
                    ['value' => 'Email', 'label' => 'Email'],
                    ['value' => 'SMS', 'label' => 'SMS'],
                ]],
            ],
            'rememberFilters' => ['q', 'channel'],
            'sortKey' => 'name',
        ]) ?>
    </div>
</section>

<section class="card mb-3" id="demo-notices">
    <div class="card-body">
        <h2 class="h6 text-uppercase text-body-secondary small fw-bold">Notices</h2>
        <div class="d-flex flex-column gap-2">
            <?= $this->element('ui/notice', [
                'tone' => 'info',
                'icon' => 'bi bi-info-circle',
                'text' => 'A change takes effect once it is approved. Until then it waits as a proposal.',
            ]) ?>
            <?= $this->element('ui/notice', [
                'tone' => 'tag',
                'title' => 'Edited in review',
                'html' => 'The original proposal is kept. <a href="#original">Show the change</a>.',
            ]) ?>
            <?= $this->element('ui/notice', [
                'tone' => 'ok',
                'icon' => 'bi bi-check-circle',
                'title' => 'All messages delivered.',
                'text' => 'The last batch went out at 08.14.',
            ]) ?>
            <?= $this->element('ui/notice', [
                'tone' => 'pending',
                'icon' => 'bi bi-hourglass-split',
                'title' => 'Not verified.',
                'text' => 'These are the sender\'s own claims about the state of the work. Check them before relying on them.',
            ]) ?>
            <?= $this->element('ui/notice', [
                'tone' => 'warning',
                'icon' => 'bi bi-exclamation-triangle',
                'title' => 'The draft is out of date.',
                'text' => 'The provider changed after the draft was made.',
                'action' => '<a href="#compare" class="btn btn-sm btn-outline-secondary">Compare</a>',
            ]) ?>
            <?= $this->element('ui/notice', [
                'tone' => 'danger',
                'icon' => 'bi bi-x-octagon',
                'title' => 'Sending is stopped.',
                'text' => 'The gateway refused the last three messages.',
                'action' => '<a href="#retry" class="btn btn-sm btn-outline-danger">Retry</a>',
            ]) ?>
            <?= $this->element('ui/notice', [
                'tone' => 'muted',
                'text' => 'Messages older than 90 days are not shown.',
            ]) ?>
        </div>
    </div>
</section>

<div class="row g-3 mb-3">
    <div class="col-12 col-lg-6">
        <section class="card h-100" id="demo-modal"><div class="card-body">
            <h2 class="h6 text-uppercase text-body-secondary small fw-bold">Modal</h2>
            <p class="small text-body-secondary">One modal, opened by the button or by a kebab's button item: the header holds the title, the move handle and the close button. Its body is the page's own form, with the date picker and the file drop.</p>
            <div class="d-flex align-items-center gap-2">
                <button type="button" class="btn btn-primary" data-kk-modal-open="demo-schedule" id="demo-open-modal">Schedule a message…</button>
                <?= $this->element('ui/kebab', ['size' => 'md', 'label' => 'More scheduling actions', 'items' => [
                    // A button item: no address, a script acts on it; here it opens the modal.
                    [
                        'type' => 'button',
                        'title' => 'Schedule from the menu…',
                        'icon' => 'bi bi-calendar-plus',
                        'attributes' => ['data-kk-modal-open' => 'demo-schedule', 'id' => 'demo-kebab-schedule'],
                    ],
                    ['type' => 'button', 'title' => 'Pause sending', 'icon' => 'bi bi-pause', 'isDisabled' => true],
                ]]) ?>
            </div>
            <?= $this->element('ui/modal', [
                'name' => 'demo-schedule',
                'title' => 'Schedule a message',
                'body' => $this->Form->create(null, ['url' => '#', 'id' => 'demo-schedule-form', 'type' => 'file'])
                    . '<div class="mb-3">' . $this->Form->control('subject', [
                        'class' => 'form-control', 'label' => ['class' => 'form-label', 'text' => 'Subject'],
                        'value' => 'Opening hours over the holidays',
                    ]) . '</div>'
                    . '<div class="mb-3">' . $this->element('ui/date_time_picker', [
                        'name' => 'send_at', 'id' => 'demo-send-at', 'label' => 'Send at', 'value' => $sendAfter,
                        'help' => 'Helsinki time.',
                    ]) . '</div>'
                    . $this->element('ui/file_drop', [
                        'name' => 'attachment', 'id' => 'demo-modal-attachment', 'label' => 'Attachment',
                        'accept' => '.pdf,image/*', 'maxBytes' => 5 * 1024 * 1024, 'hint' => 'PDF or an image, at most 5 MB.',
                    ])
                    . $this->Form->end(),
                'footer' => '<button type="button" class="btn btn-outline-secondary" data-kk-modal-close>Cancel</button>'
                    . '<button type="button" class="btn btn-primary" data-kk-modal-close>Schedule</button>',
            ]) ?>
        </div></section>
    </div>
    <div class="col-12 col-lg-6">
        <section class="card h-100" id="demo-format"><div class="card-body">
            <h2 class="h6 text-uppercase text-body-secondary small fw-bold">The same formats in a script</h2>
            <p class="small text-body-secondary">The PHP helper's output, and window.KkFormat's beside it.</p>
            <table class="table table-sm small mb-0">
                <thead><tr><th scope="col">Call</th><th scope="col">PHP</th><th scope="col">Script</th></tr></thead>
                <tbody>
                <tr><td>date</td><td><?= h($this->Ui->date('2026-10-26T22:30:00Z')) ?></td><td data-demo-format="date" data-value="2026-10-26T22:30:00Z"></td></tr>
                <tr><td>dateTime</td><td><?= h($this->Ui->dateTime('2026-10-26T06:14:00Z')) ?></td><td data-demo-format="dateTime" data-value="2026-10-26T06:14:00Z"></td></tr>
                <tr><td>number</td><td><?= h($this->Ui->number(1842.3, 2)) ?></td><td data-demo-format="number" data-value="1842.3"></td></tr>
                <tr><td>money</td><td><?= h($this->Ui->money('1842.30')) ?></td><td data-demo-format="money" data-value="1842.30"></td></tr>
                <tr><td>percent</td><td><?= h($this->Ui->percent(0.0425, 1)) ?></td><td data-demo-format="percent" data-value="0.0425"></td></tr>
                <?php // A tiny negative rounds to zero and shows no minus. ?>
                <tr><td>number −0.004</td><td><?= h($this->Ui->number(-0.004, 2)) ?></td><td data-demo-format="number" data-value="-0.004"></td></tr>
                <tr><td>money −0.001</td><td><?= h($this->Ui->money(-0.001)) ?></td><td data-demo-format="money" data-value="-0.001"></td></tr>
                <tr><td>percent −0.0004</td><td><?= h($this->Ui->percent(-0.0004, 1)) ?></td><td data-demo-format="percent" data-value="-0.0004"></td></tr>
                </tbody>
            </table>
        </div></section>
    </div>
</div>

<section class="card mb-3" id="demo-vue">
    <div class="card-body">
        <h2 class="h6 text-uppercase text-body-secondary small fw-bold">From a script: the components in a Vue app</h2>
        <p class="small text-body-secondary">A render function (no template compiler) using the same components: all rows in the page, sorted and filtered in the browser, paged with the pager.</p>
        <div data-demo-vue-app></div>
    </div>
</section>

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
                <dt class="col-5 fw-normal text-body-secondary">money()</dt><dd class="col-7"><?= h($this->Ui->money('1842.30')) ?></dd>
                <dt class="col-5 fw-normal text-body-secondary">percent()</dt><dd class="col-7 mb-0"><?= h($this->Ui->percent(0.75)) ?></dd>
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

<div class="row g-3 mb-3"><div class="col-12 col-lg-6">
<section class="card" id="demo-expand-toggle">
    <div class="card-body pb-0">
        <h2 class="h6 text-uppercase text-body-secondary small fw-bold">Expand toggle</h2>
        <dl class="row small mb-2">
            <dt class="col-5 text-body-secondary fw-normal">Provider</dt><dd class="col-7 mb-1">Example Gateway</dd>
            <dt class="col-5 text-body-secondary fw-normal">Sender</dt><dd class="col-7 mb-1">Example Sender</dd>
        </dl>
        <dl class="row small mb-2" id="demo-expand-more" hidden>
            <dt class="col-5 text-body-secondary fw-normal">Reply address</dt><dd class="col-7 mb-1">replies@example.test</dd>
            <dt class="col-5 text-body-secondary fw-normal">Send after</dt><dd class="col-7 mb-1">08.00</dd>
            <dt class="col-5 text-body-secondary fw-normal">Daily limit</dt><dd class="col-7 mb-1">500 messages</dd>
        </dl>
    </div>
    <?= $this->element('ui/expand_toggle', ['target' => 'demo-expand-more']) ?>
    <div class="card-footer bg-white small text-body-secondary" id="demo-expand-state">kk:expand-toggle: not yet dispatched</div>
</section>
</div></div>

<section class="card mb-3 kk-page-narrow">
    <div class="card-body">
        <h2 class="h6 text-uppercase text-body-secondary small fw-bold">Form: label, help text, validation, date picker, file drop</h2>
        <ul class="nav nav-tabs nav-tabs-scrollable mb-3" id="demo-tabs">
            <li class="nav-item"><a class="nav-link active" aria-current="page" href="#">Sender</a></li>
            <li class="nav-item"><a class="nav-link" href="#">Delivery windows</a></li>
            <li class="nav-item"><a class="nav-link" href="#">Rate limits</a></li>
            <li class="nav-item"><a class="nav-link" href="#">Bounce handling</a></li>
            <li class="nav-item"><a class="nav-link" href="#">Audit trail</a></li>
        </ul>
        <?= $this->Form->create($settingsForm, ['url' => '#', 'type' => 'file']) ?>
        <div class="row g-3">
            <div class="col-12 col-md-6">
                <?= $this->Form->control('sender_name', [
                    'class' => 'form-control',
                    'label' => ['class' => 'form-label', 'text' => 'Sender name'],
                    'help' => 'Shown to the recipient as the sender.',
                ]) ?>
            </div>
            <div class="col-12 col-md-6">
                <?= $this->element('ui/date_time_picker', [
                    'name' => 'send_after',
                    'label' => 'Send after',
                    'value' => $sendAfter,
                    'help' => 'Date picker: DD.MM.YYYY HH.mm, Helsinki time.',
                ]) ?>
            </div>
            <div class="col-12 col-md-6">
                <?= $this->Form->control('reply_address', [
                    'class' => 'form-control',
                    'label' => ['class' => 'form-label', 'text' => 'Reply address'],
                    'help' => 'Replies from recipients go here.',
                ]) ?>
            </div>
            <div class="col-12 col-md-6">
                <?= $this->element('ui/file_drop', [
                    'name' => 'logo',
                    'label' => 'Logo',
                    'accept' => 'image/png,image/svg+xml',
                    'maxBytes' => 1024 * 1024,
                    'hint' => 'PNG or SVG, at most 1 MB.',
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
