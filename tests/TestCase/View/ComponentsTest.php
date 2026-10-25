<?php
declare(strict_types=1);

namespace RockSoftware\Ui\Test\TestCase\View;

use Cake\Core\Configure;
use Cake\I18n\I18n;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use RockSoftware\Ui\UiPlugin;

/**
 * The optional components load only when the application switches them on.
 */
class ComponentsTest extends TestCase
{
    use IntegrationTestTrait;

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

    /**
     * @param array<string, bool> $components The components switched on or off.
     * @return void
     */
    private function useComponents(array $components): void
    {
        Configure::write('Ui', ['appName' => 'Demo App', 'components' => $components]);
        UiPlugin::applyDefaults();
    }

    public function testNoComponentLoadsByDefault(): void
    {
        $this->useComponents([]);
        $this->get('/reports');

        $this->assertResponseNotContains('kk-modal');
        $this->assertResponseNotContains('kk-list-table');
        $this->assertResponseNotContains('kk-breadcrumb');
        $this->assertResponseNotContains('kk-ui-texts');
        $this->assertResponseNotContains('kk-confirm');
        $this->assertResponseNotContains('kk-toast');
        $this->assertResponseContains('class="kk-brand"');
    }

    public function testSharedPiecesStylesLoadOnEveryLayout(): void
    {
        $this->useComponents([]);
        $this->get('/');
        $this->assertResponseContains('/rock_software/ui/css/kk-components.css');

        $this->get('/dashboard/sign-in');
        $this->assertResponseContains('/rock_software/ui/css/kk-components.css');
    }

    public function testConfirmLoadsItsStyleScriptAndTexts(): void
    {
        $this->useComponents(['confirm' => true]);
        $this->get('/');

        $this->assertResponseContains('/rock_software/ui/css/kk-confirm.css');
        $this->assertResponseContains('/rock_software/ui/js/components/kk-confirm.js');
        $this->assertResponseContains('"confirmTitle":"Are you sure?"');
        $this->assertResponseContains('"confirmButton":"Confirm"');
        $this->assertResponseContains('"confirmCancel":"Cancel"');
        $this->assertResponseNotContains('kk-toast');
    }

    public function testToastLoadsItsStyleScriptAndTexts(): void
    {
        $this->useComponents(['toast' => true]);
        $this->get('/');

        $this->assertResponseContains('/rock_software/ui/css/kk-toast.css');
        $this->assertResponseContains('/rock_software/ui/js/components/kk-toast.js');
        $this->assertResponseContains('"toastClose":"Close"');
        $this->assertResponseNotContains('kk-confirm');
    }

    public function testConfirmAndToastTextsInFinnish(): void
    {
        I18n::setLocale('fi_FI');
        $this->useComponents(['confirm' => true, 'toast' => true]);
        $this->get('/');

        $this->assertResponseContains('"confirmTitle":"Oletko varma?"');
        $this->assertResponseContains('"confirmButton":"Vahvista"');
        $this->assertResponseContains('"confirmCancel":"Peruuta"');
        $this->assertResponseContains('"toastClose":"Sulje"');
    }

    public function testAPageWithAnAskingPostButtonLoadsConfirmItself(): void
    {
        $this->useComponents([]);
        $this->get('/components');

        $this->assertResponseOk();
        $this->assertResponseContains('/rock_software/ui/js/components/kk-confirm.js');
        $this->assertResponseContains('/rock_software/ui/css/kk-confirm.css');
        // The page asks for the toasts in its template.
        $this->assertResponseContains('/rock_software/ui/js/components/kk-toast.js');
    }

    public function testModalLoadsItsStyleScriptsAndTexts(): void
    {
        $this->useComponents(['modal' => true]);
        $this->get('/');

        $this->assertResponseContains('/rock_software/ui/css/kk-modal.css');
        $this->assertResponseContains('/rock_software/ui/js/components/kk-modal.js');
        $this->assertResponseContains('/rock_software/ui/js/components/kk-modal-markup.js');
        $this->assertResponseContains('<script type="application/json" id="kk-ui-texts">');
        $this->assertResponseContains('"modalClose":"Close"');
        $this->assertResponseNotContains('kk-list-table');
    }

    public function testListTableLoadsItsStyleScriptAndTexts(): void
    {
        $this->useComponents(['listTable' => true]);
        $this->get('/');

        $this->assertResponseContains('/rock_software/ui/css/kk-list-table.css');
        $this->assertResponseContains('/rock_software/ui/js/components/kk-list-table.js');
        $this->assertResponseContains('"listAll":"All"');
        $this->assertResponseNotContains('kk-modal');
    }

    public function testComponentTextsFollowTheLocale(): void
    {
        I18n::setLocale('fi_FI');
        $this->useComponents(['modal' => true, 'listTable' => true]);
        $this->get('/');

        $this->assertResponseContains('"modalClose":"Sulje"');
        $this->assertResponseContains('"listAll":"Kaikki"');
        $this->assertResponseContains('"locale":"fi"');
    }

    public function testBreadcrumbTakesTheBrandsPlace(): void
    {
        $this->useComponents(['breadcrumb' => true]);
        $this->get('/reports/view/7');

        $this->assertResponseContains('/rock_software/ui/css/kk-breadcrumb.css');
        $this->assertResponseContains('<nav class="kk-breadcrumb d-none d-md-block" aria-label="Breadcrumb">');
        $this->assertResponseContains('<a href="/reports">Reports</a>');
        $this->assertResponseContains('<li class="breadcrumb-item active" aria-current="page">');
        $this->assertResponseContains('<span class="">Report 7</span>');
        $this->assertResponseContains('class="kk-breadcrumb-back"');
        $this->assertResponseNotContains('class="kk-brand"');
    }

    public function testComponentsStayOffOnTheSignInPage(): void
    {
        $this->useComponents(['modal' => true, 'listTable' => true, 'breadcrumb' => true]);
        $this->get('/dashboard/sign-in');

        $this->assertResponseNotContains('kk-modal');
        $this->assertResponseNotContains('kk-list-table');
    }
}
