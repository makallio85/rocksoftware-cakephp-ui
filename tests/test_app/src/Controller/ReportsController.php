<?php
declare(strict_types=1);

namespace TestApp\Controller;

/**
 * A page under a menu group other than the first, with a breadcrumb.
 */
class ReportsController extends AppController
{
    /**
     * @return void
     */
    public function index(): void
    {
        $this->set('breadcrumb', [
            ['title' => 'Work', 'url' => null],
            ['title' => 'Reports', 'url' => null],
        ]);
    }

    /**
     * @param string $id The report.
     * @return void
     */
    public function view(string $id): void
    {
        $this->set('breadcrumb', [
            ['title' => 'Work', 'url' => null],
            ['title' => 'Reports', 'url' => ['controller' => 'Reports', 'action' => 'index']],
            ['title' => 'Report ' . $id, 'url' => null],
        ]);
    }
}
