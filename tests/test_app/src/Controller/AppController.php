<?php
declare(strict_types=1);

namespace TestApp\Controller;

use Cake\Controller\Controller;
use Cake\Core\Configure;
use Cake\Event\EventInterface;
use TestApp\View\DemoMainMenu;

/**
 * Gives the layout the main menu and the user menu, as an application's
 * AppController does. Tests set the counts and the user menu in `Configure`.
 */
class AppController extends Controller
{
    /**
     * @return void
     */
    public function initialize(): void
    {
        parent::initialize();

        $this->loadComponent('Flash');
    }

    /**
     * @param \Cake\Event\EventInterface<\Cake\Controller\Controller> $event The event.
     * @return void
     */
    public function beforeRender(EventInterface $event): void
    {
        parent::beforeRender($event);

        $this->set('mainMenu', new DemoMainMenu((array)Configure::read('TestApp.badgeCounts', [])));
        $userMenuItems = Configure::read('TestApp.userMenuItems');
        if (is_array($userMenuItems)) {
            $this->set('userMenuItems', $userMenuItems);
        }
    }
}
