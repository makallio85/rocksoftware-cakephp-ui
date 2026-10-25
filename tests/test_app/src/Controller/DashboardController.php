<?php
declare(strict_types=1);

namespace TestApp\Controller;

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
}
