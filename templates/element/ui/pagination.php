<?php
/**
 * The pages of a paginated list: "Showing 1–20 of 240" and the page links,
 * with the plugin's Bootstrap 5 templates (`UiHelper::PAGINATOR_TEMPLATES`),
 * which apply to this element only. Renders nothing for an empty list; the
 * links only when there is more than one page.
 *
 *     <?= $this->element('ui/pagination') ?>
 *     <?= $this->element('ui/pagination', ['results' => $messages, 'modulus' => 6]) ?>
 *
 * @var \Cake\View\View $this
 * @var \Cake\Datasource\Paging\PaginatedInterface|null $results The paginated rows, when the
 *   page has several sets; by default the paginator finds the page's one.
 * @var int|null $modulus How many page numbers show around the current one; default 4.
 * @var bool|null $showsCounter Whether "Showing … of …" shows; default true.
 */
use Cake\Datasource\Paging\PaginatedInterface;
use RockSoftware\Ui\View\Helper\UiHelper;

$this->loadHelper('Paginator');
if (isset($results) && $results instanceof PaginatedInterface) {
    $this->Paginator->setPaginated($results);
}
$templater = $this->Paginator->templater();
$templater->push();
$this->Paginator->setTemplates(UiHelper::PAGINATOR_TEMPLATES);

$count = (int)$this->Paginator->param('totalCount');
$pageCount = (int)$this->Paginator->total();
$html = '';
if ($count > 0) {
    $counter = ($showsCounter ?? true) === false ? '' : '<span class="kk-pagination-counter">'
        . $this->Paginator->counter(
            __d('rock_software_ui', 'Showing {{start}}–{{end}} of {{count}}'),
        ) . '</span>';
    $links = '';
    if ($pageCount > 1) {
        $previous = '<i class="bi bi-chevron-left" aria-hidden="true"></i><span class="visually-hidden">'
            . h(__d('rock_software_ui', 'Previous page')) . '</span>';
        $next = '<i class="bi bi-chevron-right" aria-hidden="true"></i><span class="visually-hidden">'
            . h(__d('rock_software_ui', 'Next page')) . '</span>';
        $links = '<nav aria-label="' . h(__d('rock_software_ui', 'Pages')) . '">'
            . '<ul class="pagination kk-pagination">'
            . $this->Paginator->prev($previous, ['escape' => false])
            . $this->Paginator->numbers(['modulus' => (int)($modulus ?? 4), 'first' => 1, 'last' => 1])
            . $this->Paginator->next($next, ['escape' => false])
            . '</ul></nav>';
    }
    $html = '<div class="kk-pagination-bar">' . $counter . $links . '</div>';
}
$templater->pop();
echo $html;
