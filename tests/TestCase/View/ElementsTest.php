<?php
declare(strict_types=1);

namespace RockSoftware\Ui\Test\TestCase\View;

use ArrayIterator;
use Cake\Core\Configure;
use Cake\Datasource\Paging\PaginatedResultSet;
use Cake\Http\ServerRequest;
use Cake\I18n\DateTime;
use Cake\I18n\I18n;
use Cake\Routing\Route\DashedRoute;
use Cake\Routing\RouteBuilder;
use Cake\Routing\Router;
use Cake\TestSuite\TestCase;
use RockSoftware\Ui\UiPlugin;
use RockSoftware\Ui\View\Helper\UiHelper;
use TestApp\View\AppView;

/**
 * The shared elements: `ui/page_header`, `ui/kebab`, `ui/empty_state`,
 * `ui/pagination`, and the Vue components' markup: `ui/modal`,
 * `ui/date_time_picker`, `ui/file_drop` and `ui/list_table`.
 */
class ElementsTest extends TestCase
{
    private AppView $view;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        Configure::write('Ui', []);
        UiPlugin::applyDefaults();
        Router::reload();
        Router::createRouteBuilder('/')->scope('/', function (RouteBuilder $routes): void {
            $routes->setRouteClass(DashedRoute::class);
            $routes->fallbacks();
        });
        $request = new ServerRequest(['url' => '/messages', 'params' => [
            'plugin' => null, 'controller' => 'Messages', 'action' => 'index', 'pass' => [],
        ]]);
        Router::setRequest($request);
        $this->view = new AppView($request);
    }

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        I18n::setLocale('en_US');
        Configure::delete('Ui');
        UiPlugin::applyDefaults();
        parent::tearDown();
    }

    public function testKebabListsLinksPostsHeadersTextAndDividers(): void
    {
        $html = $this->view->element('ui/kebab', ['items' => [
            ['type' => 'header', 'title' => 'View'],
            ['type' => 'text', 'html' => '<span id="switch">switch</span>'],
            ['title' => 'Open <7>', 'url' => '/messages/view/7', 'icon' => 'bi bi-eye'],
            ['title' => 'Resend', 'url' => '/messages/resend/7', 'isDisabled' => true],
            ['type' => 'divider'],
            ['type' => 'post', 'title' => 'Delete', 'url' => '/messages/delete/7', 'confirm' => 'Delete it?',
                'isDestructive' => true],
        ]]);

        $this->assertStringContainsString('<div class="dropdown kk-kebab">', $html);
        $this->assertStringContainsString(
            '<button type="button" class="btn btn-outline-secondary kk-kebab-toggle btn-sm" data-bs-toggle="dropdown"',
            $html,
        );
        $this->assertStringContainsString('aria-label="Actions"', $html);
        $this->assertStringContainsString('<i class="bi bi-three-dots-vertical" aria-hidden="true"></i>', $html);
        $this->assertStringContainsString('<ul class="dropdown-menu dropdown-menu-end shadow-sm">', $html);
        $this->assertStringContainsString('<li><h6 class="dropdown-header">View</h6></li>', $html);
        $this->assertStringContainsString('<div class="dropdown-item-text"><span id="switch">switch</span></div>', $html);
        $this->assertStringContainsString(
            '<a href="/messages/view/7" class="dropdown-item"><i class="bi bi-eye" aria-hidden="true"></i> Open &lt;7&gt;</a>',
            $html,
        );
        $this->assertStringContainsString('<span class="dropdown-item disabled" aria-disabled="true">Resend</span>', $html);
        $this->assertStringContainsString('<li><hr class="dropdown-divider"></li>', $html);
        $this->assertStringContainsString('action="/messages/delete/7"', $html);
        $this->assertStringContainsString('class="dropdown-item text-danger" data-confirm="Delete it?"', $html);
        $this->assertStringNotContainsString('onclick', $html);
        $this->assertTrue($this->ui()->isEnabled('confirm'));
    }

    public function testKebabButtonItemRunsAScript(): void
    {
        $html = $this->view->element('ui/kebab', ['items' => [
            ['type' => 'button', 'title' => 'Archive <7>…', 'icon' => 'bi bi-archive', 'isDestructive' => true,
                'attributes' => ['data-kk-modal-open' => 'archive-7', 'id' => 'archive-opener']],
            ['type' => 'button', 'title' => 'Pause', 'isDisabled' => true],
        ]]);

        $this->assertStringContainsString(
            '<li><button type="button" class="dropdown-item text-danger" data-kk-modal-open="archive-7" id="archive-opener">'
            . '<i class="bi bi-archive" aria-hidden="true"></i> Archive &lt;7&gt;…</button></li>',
            $html,
        );
        $this->assertStringContainsString(
            '<li><button type="button" class="dropdown-item" disabled="disabled">Pause</button></li>',
            $html,
        );
        $this->assertStringNotContainsString('dropdown-item-text', $html);
    }

    public function testKebabWithNothingInItRendersNothing(): void
    {
        $this->assertSame('', trim($this->view->element('ui/kebab', ['items' => []])));
        $this->assertSame('', trim($this->view->element('ui/kebab', ['items' => [['type' => 'divider']]])));
    }

    public function testKebabTakesALabelAndASize(): void
    {
        $html = $this->view->element('ui/kebab', [
            'items' => [['title' => 'Export', 'url' => '/export']],
            'label' => 'Message actions',
            'size' => 'md',
        ]);

        $this->assertStringContainsString('aria-label="Message actions"', $html);
        $this->assertStringContainsString('class="btn btn-outline-secondary kk-kebab-toggle"', $html);
    }

    public function testKebabLabelIsTranslated(): void
    {
        I18n::setLocale('fi_FI');

        $this->assertStringContainsString(
            'aria-label="Toiminnot"',
            $this->view->element('ui/kebab', ['items' => [['title' => 'Vie', 'url' => '/export']]]),
        );
    }

    public function testPageHeaderWithTitleSubtitleActionsAndMenu(): void
    {
        $html = $this->view->element('ui/page_header', [
            'title' => 'Messages <all>',
            'subtitle' => 'Sent in the last 90 days.',
            'badges' => $this->ui()->badge('Live', 'ok'),
            'actions' => [
                ['title' => 'New message', 'url' => '/messages/add', 'icon' => 'bi bi-plus-lg', 'isPrimary' => true],
                ['title' => 'Refresh', 'type' => 'button', 'attributes' => ['id' => 'refresh']],
                ['title' => 'Purge', 'type' => 'post', 'url' => '/messages/purge', 'confirm' => 'Purge all?'],
            ],
            'menu' => [['title' => 'Export', 'url' => '/messages/export']],
        ]);

        $this->assertStringContainsString('<div class="kk-page-header">', $html);
        $this->assertStringContainsString(
            '<h1 class="kk-page-header-title"><span>Messages &lt;all&gt;</span>'
            . '<span class="kk-badge kk-badge-ok">Live</span></h1>',
            $html,
        );
        $this->assertStringContainsString('<p class="kk-page-header-subtitle">Sent in the last 90 days.</p>', $html);
        $this->assertStringContainsString(
            '<a href="/messages/add" class="btn btn-primary" aria-label="New message">'
            . '<i class="bi bi-plus-lg" aria-hidden="true"></i><span class="kk-page-header-label">New message</span></a>',
            $html,
        );
        $this->assertStringContainsString(
            '<button type="button" id="refresh" class="btn btn-outline-secondary"><span class="">Refresh</span></button>',
            $html,
        );
        $this->assertStringContainsString('action="/messages/purge"', $html);
        $this->assertStringContainsString('data-confirm="Purge all?"', $html);
        $this->assertStringContainsString('aria-label="More actions"', $html);
        $this->assertStringContainsString('<a href="/messages/export" class="dropdown-item">', $html);
    }

    public function testPageHeaderWithTitleOnly(): void
    {
        $html = $this->view->element('ui/page_header', ['title' => 'Messages']);

        $this->assertStringContainsString('<span>Messages</span></h1>', $html);
        $this->assertStringNotContainsString('kk-page-header-subtitle', $html);
        $this->assertStringNotContainsString('kk-page-header-actions', $html);
        $this->assertStringNotContainsString('dropdown', $html);
    }

    public function testPageHeaderTakesActionsAsMarkup(): void
    {
        $html = $this->view->element('ui/page_header', ['title' => 'Messages', 'actions' => '<a id="own">Own</a>']);

        $this->assertStringContainsString('<div class="kk-page-header-actions"><a id="own">Own</a></div>', $html);
    }

    public function testEmptyState(): void
    {
        $html = $this->view->element('ui/empty_state', [
            'title' => 'No messages yet',
            'text' => 'Messages appear here once one is sent.',
            'icon' => 'bi bi-inbox',
            'action' => '<a href="/messages/add" class="btn btn-primary">New message</a>',
        ]);

        $this->assertStringContainsString('<div class="kk-empty-state">', $html);
        $this->assertStringContainsString('<i class="kk-empty-state-icon bi bi-inbox" aria-hidden="true"></i>', $html);
        $this->assertStringContainsString('<div class="kk-empty-state-title">No messages yet</div>', $html);
        $this->assertStringContainsString('<div class="kk-empty-state-text">Messages appear here once one is sent.</div>', $html);
        $this->assertStringContainsString('<div class="kk-empty-state-action"><a href="/messages/add"', $html);
    }

    public function testEmptyStateWithTitleOnly(): void
    {
        $html = $this->view->element('ui/empty_state', ['title' => 'Nothing <here>']);

        $this->assertStringContainsString('<div class="kk-empty-state-title">Nothing &lt;here&gt;</div>', $html);
        $this->assertStringNotContainsString('kk-empty-state-text', $html);
        $this->assertStringNotContainsString('kk-empty-state-icon', $html);
    }

    public function testPaginationOnTheFirstPage(): void
    {
        $html = $this->view->element('ui/pagination', ['results' => $this->paginated(1, 47, 4)]);

        $this->assertStringContainsString('<span class="kk-pagination-counter">Showing 1–4 of 47</span>', $html);
        $this->assertStringContainsString('<nav aria-label="Pages"><ul class="pagination kk-pagination">', $html);
        $this->assertStringContainsString(
            '<li class="page-item disabled"><span class="page-link"><i class="bi bi-chevron-left" aria-hidden="true"></i>'
            . '<span class="visually-hidden">Previous page</span></span></li>',
            $html,
        );
        $this->assertStringContainsString(
            '<li class="page-item active" aria-current="page"><span class="page-link">1</span></li>',
            $html,
        );
        $this->assertStringContainsString('<a class="page-link" href="/messages?page=12">12</a>', $html);
        $this->assertStringContainsString('<li class="page-item disabled kk-page-edge"><span class="page-link">&hellip;</span></li>', $html);
        $this->assertStringContainsString('<a class="page-link" rel="next" href="/messages?page=2">', $html);
        $this->assertStringNotContainsString('onclick', $html);
    }

    public function testPaginationTemplatesStayInTheElement(): void
    {
        $this->view->element('ui/pagination', ['results' => $this->paginated(2, 47, 4)]);

        $this->assertSame('<li><a href="{{url}}">{{text}}</a></li>', $this->view->Paginator->templater()->get('number'));
    }

    public function testPaginationOfOnePageShowsTheCounterOnly(): void
    {
        $html = $this->view->element('ui/pagination', ['results' => $this->paginated(1, 3, 20)]);

        $this->assertStringContainsString('Showing 1–3 of 3', $html);
        $this->assertStringNotContainsString('<nav', $html);
    }

    public function testPaginationOfNothingRendersNothing(): void
    {
        $this->assertSame('', trim($this->view->element('ui/pagination', ['results' => $this->paginated(1, 0, 20)])));
    }

    public function testPaginationWithoutTheCounter(): void
    {
        $html = $this->view->element('ui/pagination', [
            'results' => $this->paginated(1, 47, 4),
            'showsCounter' => false,
        ]);

        $this->assertStringNotContainsString('kk-pagination-counter', $html);
        $this->assertStringContainsString('kk-pagination', $html);
    }

    public function testPaginationInFinnish(): void
    {
        I18n::setLocale('fi_FI');
        $html = $this->view->element('ui/pagination', ['results' => $this->paginated(1, 47, 4)]);

        $this->assertStringContainsString('Näytetään 1–4 / 47', $html);
        $this->assertStringContainsString('aria-label="Sivut"', $html);
        $this->assertStringContainsString('Seuraava sivu', $html);
    }

    public function testModalWritesItsMarkupAndLoadsTheComponent(): void
    {
        $html = $this->view->element('ui/modal', [
            'name' => 'reject-12',
            'title' => 'Reject <the> request?',
            'body' => '<p>Body</p>',
            'footer' => '<button type="button" data-kk-modal-close>Cancel</button>',
            'size' => 'lg',
        ]);

        $this->assertStringContainsString('<div data-kk-modal="reject-12" data-title="Reject &lt;the&gt; request?" data-size="lg"', $html);
        $this->assertStringContainsString('data-closable="true" hidden><p>Body</p><div data-kk-modal-footer>'
            . '<button type="button" data-kk-modal-close>Cancel</button></div></div>', $html);
        $this->assertTrue($this->ui()->isEnabled('modal'));
        $this->assertFalse($this->ui()->isEnabled('vue'));
    }

    public function testModalWithoutFooterOrClose(): void
    {
        $html = $this->view->element('ui/modal', ['name' => 'm', 'title' => 'T', 'body' => 'B', 'isClosable' => false]);

        $this->assertStringContainsString('data-closable="false"', $html);
        $this->assertStringNotContainsString('data-kk-modal-footer', $html);
    }

    public function testDateTimePickerShowsAMomentInHelsinkiAsThePickerValue(): void
    {
        $html = $this->view->element('ui/date_time_picker', [
            'name' => 'send_after',
            'label' => 'Send after',
            'value' => new DateTime('2026-10-27 06:00:00', 'UTC'),
            'help' => 'Helsinki time.',
            'error' => 'Pick a later time.',
            'timeStep' => 15,
            'min' => '2026-10-26',
        ]);

        $this->assertStringContainsString('<label class="form-label" for="send-after">Send after</label>', $html);
        $this->assertStringContainsString('<input type="text" name="send_after" id="send-after" value="2026-10-27 08:00"'
            . ' class="form-control is-invalid" data-kk-date-time-picker data-mode="datetime" data-time-step="15"'
            . ' data-min="2026-10-26" aria-describedby="send-after-help send-after-error" autocomplete="off">', $html);
        $this->assertStringContainsString('<div class="invalid-feedback d-block" id="send-after-error">Pick a later time.</div>', $html);
        $this->assertStringContainsString('<div class="form-text" id="send-after-help">Helsinki time.</div>', $html);
        $this->assertTrue($this->ui()->isEnabled('dateTimePicker'));
    }

    public function testDateTimePickerKeepsAPostedValueAsItIs(): void
    {
        $html = $this->view->element('ui/date_time_picker', ['name' => 'day', 'value' => '2026-10-27', 'mode' => 'date', 'isRequired' => true]);

        $this->assertStringContainsString('value="2026-10-27"', $html);
        $this->assertStringContainsString('data-mode="date"', $html);
        $this->assertStringContainsString(' required ', $html);
        $this->assertStringNotContainsString('<label', $html);
    }

    public function testFileDropWritesAFileInputAndLoadsTheComponent(): void
    {
        $html = $this->view->element('ui/file_drop', [
            'name' => 'attachments',
            'label' => 'Attachments',
            'accept' => '.pdf,image/*',
            'isMultiple' => true,
            'maxBytes' => 1048576,
            'hint' => 'At most 1 MB.',
        ]);

        $this->assertStringContainsString('<input type="file" name="attachments[]" id="attachments" accept=".pdf,image/*" multiple'
            . ' data-kk-file-drop data-max-bytes="1048576" data-hint="At most 1 MB." class="form-control">', $html);
        $this->assertTrue($this->ui()->isEnabled('fileDrop'));
    }

    public function testListTableWritesItsRowsAsJsonSafeInAScriptBlock(): void
    {
        $html = $this->view->element('ui/list_table', [
            'id' => 'suppliers',
            'columns' => [['key' => 'name', 'label' => 'Name']],
            'rows' => [['id' => 1, 'name' => '</script><b>North</b>']],
            'filters' => [['key' => 'name', 'label' => 'Name', 'type' => 'text']],
            'sortKey' => 'name',
            'selectable' => true,
            'selectionName' => 'ids',
            'bulk' => '<button type="submit">Delete</button>',
        ]);

        $this->assertStringContainsString('<div data-kk-list-table id="suppliers">', $html);
        $this->assertStringNotContainsString('</script><b>', $html);
        $this->assertStringContainsString('<div data-kk-list-table-bulk hidden><button type="submit">Delete</button></div>', $html);
        preg_match('#<script type="application/json" data-kk-list-table-config>(.*?)</script>#s', $html, $match);
        $config = json_decode($match[1] ?? '{}', true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('</script><b>North</b>', $config['rows'][0]['name']);
        $this->assertSame('name', $config['sortKey']);
        $this->assertTrue($config['selectable']);
        $this->assertSame('ids', $config['selectionName']);
        $this->assertArrayNotHasKey('isServerSorted', $config);
        $this->assertTrue($this->ui()->isEnabled('listTable'));
    }

    public function testListTableOfAServerPagedListTakesTheOrderAndThePagerFromThePaginator(): void
    {
        $results = new PaginatedResultSet(new ArrayIterator([['id' => 1]]), [
            'count' => 1, 'totalCount' => 9, 'perPage' => 4, 'pageCount' => 3, 'currentPage' => 2,
            'requestedPage' => 2, 'start' => 5, 'end' => 8, 'hasPrevPage' => true, 'hasNextPage' => true,
            'sort' => 'Messages.sent_at', 'direction' => 'desc', 'sortDefault' => false, 'directionDefault' => false,
            'completeSort' => [], 'alias' => 'Messages', 'scope' => null, 'limit' => null,
        ]);
        $html = $this->view->element('ui/list_table', [
            'results' => $results,
            'columns' => [['key' => 'recipient', 'label' => 'To'], ['key' => 'sentAt', 'label' => 'Sent', 'sortField' => 'sent_at']],
            'rows' => [['id' => 1, 'recipient' => 'a@example.test', 'sentAt' => '26.10.2026 08.14']],
        ]);

        preg_match('#<script type="application/json" data-kk-list-table-config>(.*?)</script>#s', $html, $match);
        $config = json_decode($match[1] ?? '{}', true, 512, JSON_THROW_ON_ERROR);
        $this->assertTrue($config['isServerSorted']);
        $this->assertSame('sentAt', $config['sortKey']);
        $this->assertFalse($config['sortAsc']);
        $this->assertStringContainsString('<div data-kk-list-table-pager hidden><div class="kk-pagination-bar">', $html);
        $this->assertStringContainsString('Showing 5–8 of 9', $html);
    }

    /**
     * @return \RockSoftware\Ui\View\Helper\UiHelper
     */
    private function ui(): UiHelper
    {
        $helper = $this->view->helpers()->get('Ui');
        $this->assertInstanceOf(UiHelper::class, $helper);

        return $helper;
    }

    /**
     * @param int $page The current page.
     * @param int $total The rows in all.
     * @param int $perPage The rows per page.
     * @return \Cake\Datasource\Paging\PaginatedResultSet<array-key, mixed>
     */
    private function paginated(int $page, int $total, int $perPage): PaginatedResultSet
    {
        $pageCount = max(1, (int)ceil($total / $perPage));
        $start = $total === 0 ? 0 : ($page - 1) * $perPage + 1;
        $end = min($total, $page * $perPage);

        return new PaginatedResultSet(new ArrayIterator(array_fill(0, max(0, $end - $start + 1), [])), [
            'count' => max(0, $end - $start + 1),
            'totalCount' => $total,
            'perPage' => $perPage,
            'pageCount' => $pageCount,
            'currentPage' => $page,
            'requestedPage' => $page,
            'start' => $start,
            'end' => $end,
            'hasPrevPage' => $page > 1,
            'hasNextPage' => $page < $pageCount,
            'sort' => null,
            'direction' => null,
            'sortDefault' => false,
            'directionDefault' => false,
            'completeSort' => [],
            'alias' => 'Messages',
            'scope' => null,
            'limit' => null,
        ]);
    }
}
