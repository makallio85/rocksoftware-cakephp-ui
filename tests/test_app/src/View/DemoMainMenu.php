<?php
declare(strict_types=1);

namespace TestApp\View;

use RockSoftware\Ui\View\MainMenuInterface;

/**
 * A fictional application's main menu.
 */
final class DemoMainMenu implements MainMenuInterface
{
    /** The reports waiting for review. */
    public const BADGE_PENDING_REPORTS = 'pendingReports';

    /**
     * @param array<string, int> $badgeCounts The count of each badge key.
     */
    public function __construct(private array $badgeCounts = [])
    {
    }

    /**
     * @inheritDoc
     */
    public function groups(): array
    {
        return [
            [
                'title' => 'Home',
                'icon' => 'bi bi-house',
                'items' => [
                    ['title' => 'Dashboard', 'url' => ['plugin' => null, 'controller' => 'Dashboard', 'action' => 'index']],
                ],
            ],
            [
                'title' => 'Work',
                'icon' => 'bi bi-briefcase',
                'items' => [
                    [
                        'title' => 'Reports',
                        'url' => ['plugin' => null, 'controller' => 'Reports', 'action' => 'index'],
                        'badge' => self::BADGE_PENDING_REPORTS,
                        'badgeLabel' => 'waiting for review',
                    ],
                    ['title' => 'Archive', 'url' => ['plugin' => null, 'controller' => 'Archive', 'action' => 'index']],
                ],
            ],
        ];
    }

    /**
     * @inheritDoc
     */
    public function badgeCounts(): array
    {
        return $this->badgeCounts;
    }
}
