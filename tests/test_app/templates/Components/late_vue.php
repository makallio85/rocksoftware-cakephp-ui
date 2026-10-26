<?php
/**
 * The markup components on a page whose Vue arrives after parsing: the
 * frame's scripts run without `window.Vue`, and the plugin's runtime is
 * loaded `defer`, as an application's Vite bundle (a module script) sets
 * `window.Vue` between parsing and DOMContentLoaded. The components mount
 * once it is there.
 *
 * @var \Cake\View\View $this
 * @var \Cake\I18n\DateTime $sendAfter
 */
$this->assign('title', 'Vue after parsing');
$this->Html->script('RockSoftware/Ui.vue.runtime.global.prod', ['block' => true, 'defer' => true]);
?>
<?= $this->element('ui/page_header', [
    'title' => 'Vue after parsing',
    'subtitle' => 'The components mount once a deferred or module script has set window.Vue.',
]) ?>

<section class="card mb-3" id="late-vue"><div class="card-body">
    <div class="d-flex align-items-center gap-2 mb-3">
        <button type="button" class="btn btn-primary" data-kk-modal-open="late-note" id="late-open-modal">Add a note…</button>
        <?= $this->element('ui/kebab', ['size' => 'md', 'items' => [
            ['type' => 'button', 'title' => 'Add a note…', 'attributes' => ['data-kk-modal-open' => 'late-note', 'id' => 'late-kebab-note']],
        ]]) ?>
    </div>
    <?= $this->element('ui/modal', [
        'name' => 'late-note',
        'title' => 'Add a note',
        'body' => '<p class="mb-0" id="late-modal-body">The modal mounted after Vue arrived.</p>',
        'footer' => '<button type="button" class="btn btn-outline-secondary" data-kk-modal-close>Close</button>',
    ]) ?>
    <div class="mb-3 col-12 col-md-4 px-0">
        <?= $this->element('ui/date_time_picker', [
            'name' => 'remind_at', 'id' => 'late-remind-at', 'label' => 'Remind at', 'value' => $sendAfter,
        ]) ?>
    </div>
    <?= $this->element('ui/list_table', [
        'id' => 'late-list',
        'rows' => [
            ['id' => 1, 'name' => 'Example Person 1', 'balance' => $this->Ui->money(-0.001)],
            ['id' => 2, 'name' => 'Example Person 2', 'balance' => $this->Ui->money(12.5)],
        ],
        'columns' => [
            ['key' => 'name', 'label' => 'Name'],
            ['key' => 'balance', 'label' => 'Balance'],
        ],
    ]) ?>
</div></section>
