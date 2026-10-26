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
        Configure::write('Ui', ['components' => $components]);
        UiPlugin::applyDefaults();
    }

    /**
     * The texts the layout wrote for the components' scripts.
     *
     * @return array<string, string>
     */
    private function texts(): array
    {
        preg_match('#<script type="application/json" id="kk-ui-texts">(.*?)</script>#s', (string)$this->_response?->getBody(), $match);

        return (array)json_decode($match[1] ?? '{}', true, 512, JSON_THROW_ON_ERROR);
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
        $this->assertResponseContains('/rock_software/ui/js/components/kk-vue-components.js');
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
        $this->assertResponseContains('/rock_software/ui/js/components/kk-vue-components.js');
        // Its date range filters use the date picker.
        $this->assertResponseContains('/rock_software/ui/css/kk-date-time-picker.css');
        $this->assertResponseContains('"listAll":"All"');
        $this->assertSame('Showing {start}–{end} of {count}', $this->texts()['pagerShowing']);
        $this->assertSame('Time', $this->texts()['pickerTimeList']);
        $this->assertSame('Europe/Helsinki', $this->texts()['timeZone']);
        $this->assertResponseNotContains('vue.runtime');
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
        $this->assertResponseContains('"numberLocale":"fi-FI"');
        $this->assertSame('Näytetään {start}–{end} / {count}', $this->texts()['pagerShowing']);
        $this->assertSame('Valitse päivä', $this->texts()['pickerDate']);
    }

    public function testVueComponentsShareOneScriptLoadedOnceAfterTheRuntime(): void
    {
        $this->useComponents(['vue' => true, 'modal' => true, 'listTable' => true, 'dateTimePicker' => true, 'fileDrop' => true]);
        $this->get('/');

        $body = (string)$this->_response?->getBody();
        $this->assertSame(1, substr_count($body, 'kk-vue-components.js'));
        $this->assertSame(1, substr_count($body, 'kk-date-time-picker.css'));
        $runtime = strpos($body, '/rock_software/ui/js/vue.runtime.global.prod.js');
        $components = strpos($body, '/rock_software/ui/js/components/kk-vue-components.js');
        $this->assertNotFalse($runtime);
        $this->assertNotFalse($components);
        $this->assertLessThan($components, $runtime);
        $this->assertResponseContains('/rock_software/ui/css/kk-file-drop.css');
        $this->assertResponseContains('"fileDropText":"Drop a file here or click to choose"');
        $this->assertResponseContains('"pickerClear":"Clear"');
    }

    public function testDateTimePickerAloneLoadsItsScriptStyleAndTexts(): void
    {
        $this->useComponents(['dateTimePicker' => true]);
        $this->get('/');

        $this->assertResponseContains('/rock_software/ui/js/components/kk-vue-components.js');
        $this->assertResponseContains('/rock_software/ui/css/kk-date-time-picker.css');
        $this->assertResponseContains('"pickerDate":"Pick a date"');
        $this->assertResponseNotContains('kk-modal-markup');
        $this->assertResponseNotContains('kk-list-table.css');
    }

    public function testFormatLoadsWithoutVue(): void
    {
        $this->useComponents(['format' => true]);
        $this->get('/');

        $this->assertResponseContains('/rock_software/ui/js/kk-format.js');
        $this->assertResponseContains('"numberLocale":"en-US"');
        $this->assertResponseNotContains('kk-vue-components');
        $this->assertResponseNotContains('vue.runtime');
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
