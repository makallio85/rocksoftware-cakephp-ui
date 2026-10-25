<?php
declare(strict_types=1);

namespace RockSoftware\Ui\View;

/**
 * The application's main menu, which the drawer renders. The application
 * sets an instance as the `mainMenu` view variable (usually in
 * `AppController::beforeRender()`).
 *
 * A group has a title, a Bootstrap Icons class (`bi bi-…`) and its items. An
 * item has a title and a URL array; it is active when the request's
 * controller and plugin match its URL's. An item with a `badge` key shows the
 * count `badgeCounts()` gives under that key when it is above zero, with
 * `badgeLabel` as the text a screen reader reads after the number.
 */
interface MainMenuInterface
{
    /**
     * The groups the signed-in person sees, in order.
     *
     * @return array<int, array{title: string, icon: string, items: array<int, array{title: string, url: array<string, mixed>, badge?: string, badgeLabel?: string}>}>
     */
    public function groups(): array;

    /**
     * The count of each badge key.
     *
     * @return array<string, int>
     */
    public function badgeCounts(): array;
}
