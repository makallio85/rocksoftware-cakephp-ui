<?php
/**
 * A `<kk-list-table>` on a page with no Vue app of its own: the rows and the
 * columns go to the component as JSON, and it draws the sortable table on
 * desktop, the cards on phones and the filters. Rendering one loads the
 * `listTable` component on the page; the page needs Vue (the application's,
 * or the `vue` component).
 *
 * All rows on the page: the component sorts and filters them (a filter with
 * `matchField`, or a text filter, which searches the plain columns; a column
 * with `sortField` sorts by that row field, not by its shown cell).
 *
 * One page of rows from the server: pass `results` (the paginated rows).
 * The order is the paginator's, a header click reloads the page with
 * `?sort=<sortField or key>&direction=…`, the `ui/pagination` element shows
 * under the rows, and a filter with `param` (`paramFrom` and `paramTo` for a
 * date range) reloads the page with its value in the query.
 *
 *     <?= $this->element('ui/list_table', [
 *         'results' => $messages,
 *         'columns' => [
 *             ['key' => 'sentAt', 'label' => 'Sent', 'sortField' => 'sent_at'],
 *             ['key' => 'recipient', 'label' => 'Recipient', 'sortable' => false],
 *             ['key' => 'status', 'label' => 'Status', 'sortable' => false, 'isHtml' => true],
 *         ],
 *         'rows' => array_map(fn($m) => [
 *             'id' => $m->id,
 *             'sentAt' => $this->Ui->dateTime($m->sent_at),
 *             'recipient' => $m->recipient,
 *             'status' => $this->Ui->badge($m->status, $m->tone),
 *             '_actions' => $this->element('ui/kebab', ['items' => …]),
 *         ], $messages->toArray()),
 *         'filters' => [['key' => 'q', 'label' => 'Search', 'type' => 'text', 'param' => 'q']],
 *         'filterValues' => ['q' => (string)$this->request->getQuery('q')],
 *     ]) ?>
 *
 * Filters a person sets for themselves can be kept in the browser:
 * `rememberFilters` names them, and their values are saved per list (the
 * keys are `<storageKeyPrefix>:list-filters:<id>:<filter key>`) and restored
 * when the page loads, over `filterValues`. Only filters narrowed here (with
 * `matchField`, or a text filter without `param`) are remembered; a server
 * filter's value is in the address. It needs the list's `id`.
 *
 *     'id' => 'handovers',
 *     'rememberFilters' => ['repository', 'status'],
 *
 * A row is plain values by column key; a column with `isHtml` holds markup
 * the template escaped (a badge, a link); `_actions` is the row's actions
 * markup (a kebab); `url` makes the row open that address when clicked
 * (with `rowClickable`).
 *
 * @var \Cake\View\View $this
 * @var array<int, array<string, mixed>> $columns `key`, `label`, `sortable` (true), `sortField`, `class`, `isHtml`.
 * @var array<int, array<string, mixed>> $rows The rows, see above.
 * @var array<int, array<string, mixed>>|null $filters `key`, `label`, `type` (`select`, `text`, `date_range`),
 *   `options` ([['value' => …, 'label' => …]]), `multiple`, `placeholder`, and `matchField` (filtered here)
 *   or `param` / `paramFrom` / `paramTo` (filtered by the server).
 * @var array<string, mixed>|null $filterValues The filters' current values by key.
 * @var \Cake\Datasource\Paging\PaginatedInterface|null $results The paginated rows of a server-paged list.
 * @var string|null $rowKey The field that identifies a row (`id`).
 * @var string|null $sortKey The column sorted by (all rows on the page).
 * @var bool|null $sortAsc Whether it runs ascending (true).
 * @var bool|null $selectable Whether rows have checkboxes.
 * @var array<int, string|int>|null $selected The keys ticked at first.
 * @var string|null $selectionName The ticked keys post as `<selectionName>[]` …
 * @var string|null $selectionForm … with the form of this id (the bulk bar's).
 * @var string|null $bulk The bulk bar's markup, shown while rows are ticked.
 * @var bool|null $rowClickable Whether a click on a row opens its `url`.
 * @var string|null $emptyText What an empty list says.
 * @var list<string>|null $rememberFilters The keys of filters whose values the browser keeps for this list.
 * @var string|null $id The wrapper's id; also the list's id in the storage keys.
 */
use Cake\Datasource\Paging\PaginatedInterface;

$this->loadHelper('RockSoftware/Ui.Ui');
$this->Ui->requireComponent('listTable');

$columns = array_values($columns ?? []);
$config = [
    'rows' => array_values($rows ?? []),
    'columns' => $columns,
    'filters' => array_values($filters ?? []),
    'filterValues' => (object)($filterValues ?? []),
    'rowKey' => (string)($rowKey ?? 'id'),
    'sortKey' => (string)($sortKey ?? ''),
    'sortAsc' => ($sortAsc ?? true) !== false,
    'selectable' => ($selectable ?? false) === true,
    'selected' => array_values($selected ?? []),
    'rowClickable' => ($rowClickable ?? false) === true,
];
$optional = [
    'selectionName' => $selectionName ?? null,
    'selectionForm' => $selectionForm ?? null,
    'emptyText' => $emptyText ?? null,
];
foreach ($optional as $key => $setting) {
    if ($setting !== null && (string)$setting !== '') {
        $config[$key] = (string)$setting;
    }
}
$rememberFilters = array_values(array_map('strval', $rememberFilters ?? []));
if ($rememberFilters !== []) {
    if (!isset($id) || (string)$id === '') {
        throw new InvalidArgumentException('A list table that remembers filters needs an id.');
    }
    $config['rememberFilters'] = $rememberFilters;
    $config['listId'] = (string)$id;
}
$pager = '';
if (isset($results) && $results instanceof PaginatedInterface) {
    $config['isServerSorted'] = true;
    $sort = (string)$results->pagingParam('sort');
    foreach ($columns as $column) {
        $field = (string)($column['sortField'] ?? $column['key']);
        if ($sort !== '' && ($field === $sort || str_ends_with($sort, '.' . $field))) {
            $config['sortKey'] = (string)$column['key'];
            $config['sortAsc'] = strtolower((string)$results->pagingParam('direction')) !== 'desc';
            break;
        }
    }
    $pager = $this->element('ui/pagination', ['results' => $results]);
}
$json = json_encode($config, JSON_THROW_ON_ERROR | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
$bulk = (string)($bulk ?? '');
?>
<div data-kk-list-table<?= isset($id) ? ' id="' . h((string)$id) . '"' : '' ?>>
<script type="application/json" data-kk-list-table-config><?= $json ?></script>
<?php if ($bulk !== '') : ?>
<div data-kk-list-table-bulk hidden><?= $bulk ?></div>
<?php endif; ?>
<?php if ($pager !== '') : ?>
<div data-kk-list-table-pager hidden><?= $pager ?></div>
<?php endif; ?>
</div>
