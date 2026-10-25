<?php
declare(strict_types=1);

namespace TestApp\Controller;

use ArrayIterator;
use Cake\Datasource\Paging\PaginatedResultSet;
use Cake\Http\Response;
use Cake\I18n\DateTime;
use TestApp\Form\DemoSettingsForm;

/**
 * Every shared in-page component on one page, with fictional data: the page
 * header, a list with badges, kebabs and pagination, the confirm dialog,
 * toasts, the empty state and a form with help texts and a validation error.
 */
class ComponentsController extends AppController
{
    /** The rows per page of the demo list. */
    private const PER_PAGE = 4;

    /** The rows of the demo list in all. */
    private const TOTAL = 47;

    /**
     * @return void
     */
    public function index(): void
    {
        $page = max(1, min((int)$this->request->getQuery('page', 1), (int)ceil(self::TOTAL / self::PER_PAGE)));
        $this->set('messages', $this->messages($page));

        $form = new DemoSettingsForm();
        $form->validate([
            'sender_name' => 'Example Sender',
            'send_after' => '27.10.2026 08.00',
            'reply_address' => 'not-an-address',
            'is_sending_enabled' => true,
        ]);
        $form->setData([
            'sender_name' => 'Example Sender',
            'send_after' => '27.10.2026 08.00',
            'reply_address' => 'not-an-address',
            'is_sending_enabled' => true,
        ]);
        $this->set('settingsForm', $form);
    }

    /**
     * The confirm dialog's target: says what would have happened.
     *
     * @param string $id The row.
     * @return \Cake\Http\Response|null
     */
    public function delete(string $id): ?Response
    {
        $this->request->allowMethod(['post', 'delete']);
        $this->Flash->success('Message ' . $id . ' deleted (demo: nothing was stored).');

        return $this->redirect(['action' => 'index']);
    }

    /**
     * One page of fictional messages, paginated as the ORM would.
     *
     * @param int $page The page.
     * @return \Cake\Datasource\Paging\PaginatedResultSet<array-key, mixed>
     */
    private function messages(int $page): PaginatedResultSet
    {
        $statuses = [
            ['Delivered', 'ok'],
            ['Queued', 'pending'],
            ['Failed', 'danger'],
            ['Not sent', 'muted'],
        ];
        $channels = ['Email', 'SMS'];
        $rows = [];
        $start = ($page - 1) * self::PER_PAGE;
        $end = min(self::TOTAL, $start + self::PER_PAGE);
        $sentAt = new DateTime('2026-10-26 06:14:00', 'UTC');
        for ($i = $start; $i < $end; $i++) {
            $rows[] = [
                'id' => self::TOTAL - $i,
                'sent_at' => $sentAt->subMinutes(37 * $i),
                'recipient' => $i % 2 === 1 ? 'Example Person ' . ($i + 1) : 'person' . ($i + 1) . '@example.test',
                'application' => 'Demo App',
                'channel' => $channels[$i % 2],
                'status' => $statuses[$i % 4][0],
                'tone' => $statuses[$i % 4][1],
            ];
        }
        $pageCount = (int)ceil(self::TOTAL / self::PER_PAGE);

        return new PaginatedResultSet(new ArrayIterator($rows), [
            'count' => count($rows),
            'totalCount' => self::TOTAL,
            'perPage' => self::PER_PAGE,
            'pageCount' => $pageCount,
            'currentPage' => $page,
            'requestedPage' => $page,
            'start' => $start + 1,
            'end' => $end,
            'hasPrevPage' => $page > 1,
            'hasNextPage' => $page < $pageCount,
            'sort' => 'sent_at',
            'direction' => 'desc',
            'sortDefault' => 'sent_at',
            'directionDefault' => 'desc',
            'completeSort' => ['sent_at' => 'desc'],
            'alias' => 'Messages',
            'scope' => null,
            'limit' => null,
        ]);
    }
}
