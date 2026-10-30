<?php
declare(strict_types=1);

namespace TestApp\Controller;

use Cake\Http\Exception\BadRequestException;
use Cake\Http\Exception\ForbiddenException;
use RuntimeException;

/**
 * Pages of a fictional application, one per layout.
 */
class DashboardController extends AppController
{
    /**
     * @return void
     */
    public function index(): void
    {
        $this->Flash->success('Saved.');
    }

    /**
     * @return void
     */
    public function signIn(): void
    {
        $this->viewBuilder()->setLayout('auth');
    }

    /**
     * @return void
     */
    public function fragment(): void
    {
        $this->viewBuilder()->setLayout('ajax');
    }

    /**
     * @return void
     */
    public function banner(): void
    {
    }

    /**
     * Fails with the status asked for, to look at the error pages:
     * `/dashboard/error/403`, `/dashboard/error/400`, `/dashboard/error/500`.
     * A missing page (`/no-such-page`) is the 404.
     *
     * @param string $code The status.
     * @return void
     * @throws \Throwable Always.
     */
    public function error(string $code = '500'): void
    {
        throw match ($code) {
            '403' => new ForbiddenException(),
            '400' => new BadRequestException(),
            default => new RuntimeException('A demo failure.'),
        };
    }
}
