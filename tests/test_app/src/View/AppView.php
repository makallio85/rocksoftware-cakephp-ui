<?php
declare(strict_types=1);

namespace TestApp\View;

use Cake\View\View;
use RockSoftware\Ui\UiPlugin;

/**
 * The application's view, themed with the frame as an application does.
 */
class AppView extends View
{
    /**
     * @return void
     */
    public function initialize(): void
    {
        $this->setTheme(UiPlugin::NAME);
        // As an application that uses the shared pieces in its templates.
        $this->addHelper('RockSoftware/Ui.Ui');
        $this->addHelper('Form', ['className' => 'RockSoftware/Ui.Form']);
    }
}
