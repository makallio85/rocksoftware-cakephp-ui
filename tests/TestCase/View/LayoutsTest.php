<?php
declare(strict_types=1);

namespace RockSoftware\Ui\Test\TestCase\View;

use Cake\Core\Configure;
use Cake\I18n\I18n;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use RockSoftware\Settings\Settings;
use RockSoftware\Settings\SettingsPlugin;
use RockSoftware\Ui\UiPlugin;
use TestApp\View\DemoMainMenu;

/**
 * The layouts as a themed application renders them, with a fictional menu.
 */
class LayoutsTest extends TestCase
{
    use IntegrationTestTrait;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        Configure::write('Ui', ['brandIcon' => 'bi bi-rocket']);
        UiPlugin::applyDefaults();
        Configure::delete('TestApp');
    }

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        I18n::setLocale('en_US');
        I18n::clear();
        UiPlugin::loadTranslations();
        Configure::delete('TestApp');
        Configure::delete('Ui');
        UiPlugin::applyDefaults();
        Settings::set(SettingsPlugin::APPLICATION_NAME, 'Demo App');
        Settings::set(SettingsPlugin::APPLICATION_ORGANIZATION_NAME, '');
        parent::tearDown();
    }

    public function testDefaultLayoutRendersTheFrame(): void
    {
        $this->get('/');

        $this->assertResponseOk();
        $this->assertResponseContains('<html lang="en">');
        $this->assertResponseContains('<title>[DEV] Dashboard · Demo App</title>');
        $this->assertResponseContains('<body class="kk-app">');
        $this->assertResponseContains('<header class="kk-topbar">');
        $this->assertResponseContains('<span class="kk-brand-mark"><i class="bi bi-rocket"></i></span>');
        $this->assertResponseContains('<span class="kk-brand-name">Demo App</span>');
        $this->assertResponseContains('id="mainMenu"');
        $this->assertResponseContains('data-storage-key-prefix="ui"');
        $this->assertResponseContains('/rock_software/ui/css/app-chrome.css');
        $this->assertResponseContains('/rock_software/ui/js/app-chrome.js');
        $this->assertResponseContains('<meta name="csrf-token"');
        $this->assertResponseContains('Saved.');
        $this->assertResponseContains('alert alert-success');
        $this->assertResponseNotContains('kk-banner');
    }

    public function testPluginServesTheFramesAssets(): void
    {
        $this->get('/rock_software/ui/css/app-chrome.css');

        $this->assertResponseOk();
        $this->assertResponseContains('.kk-topbar');
    }

    public function testMenuRendersEveryGroupAndMarksTheActiveItem(): void
    {
        $this->get('/reports');

        $body = $this->body();
        $this->assertStringContainsString('<span class="group-title">Work</span>', $body);
        $this->assertStringContainsString('<span class="group-title">Settings</span>', $body);
        $this->assertStringContainsString('<i class="group-icon bi bi-briefcase"></i>', $body);
        $this->assertSame(1, substr_count($body, 'aria-current="page"'));
        $this->assertMatchesRegularExpression('#<a href="/reports" class="active" aria-current="page">Reports</a>#', $body);
        $this->assertMatchesRegularExpression('#<a href="/preferences">Preferences</a>#', $body);
    }

    public function testLinkGroupIsOneDirectLinkWithoutFlyout(): void
    {
        $this->get('/reports');

        $body = $this->body();
        $this->assertStringContainsString(
            '<div class="kk-group kk-group-link">'
            . "\n" . '            <a href="/" class="group-btn"><i class="group-icon bi bi-house"></i>'
            . '<span class="group-title">Dashboard</span></a>',
            $body,
        );
        // One caret and one flyout per group with items, none for the link.
        $this->assertSame(2, substr_count($body, 'group-caret'));
        $this->assertSame(2, substr_count($body, '<div class="sub">'));
    }

    public function testLinkGroupIsActiveOnItsPage(): void
    {
        $this->get('/');

        $body = $this->body();
        $this->assertStringContainsString(
            '<a href="/" class="group-btn active" aria-current="page"><i class="group-icon bi bi-house"></i>',
            $body,
        );
        $this->assertSame(1, substr_count($body, 'aria-current="page"'));
    }

    public function testBadgeShowsItsCountWhenAboveZero(): void
    {
        Configure::write('TestApp.badgeCounts', [DemoMainMenu::BADGE_PENDING_REPORTS => 3]);
        $this->get('/');

        $this->assertResponseContains(
            'Reports <span class="badge rounded-pill bg-danger text-white ms-1" data-menu-badge="pendingReports">3'
            . '<span class="visually-hidden"> waiting for review</span></span>',
        );
    }

    public function testBadgeIsLeftOutAtZero(): void
    {
        Configure::write('TestApp.badgeCounts', [DemoMainMenu::BADGE_PENDING_REPORTS => 0]);
        $this->get('/');

        $this->assertResponseNotContains('data-menu-badge');
    }

    public function testUserMenuRendersItsEntries(): void
    {
        Configure::write('TestApp.userMenuItems', [
            ['type' => 'header', 'label' => 'Alex Example', 'detail' => 'alex@example.test'],
            ['type' => 'app', 'label' => 'Other app', 'url' => 'https://other.example.test/', 'current' => false],
            ['type' => 'app', 'label' => 'Demo App', 'url' => 'https://app.example.test/', 'current' => true],
            ['type' => 'link', 'label' => 'Your account', 'url' => 'https://id.example.test/account'],
            ['type' => 'sign_out', 'label' => 'Sign out', 'url' => '/sign-out'],
        ]);
        $this->get('/');

        $this->assertResponseContains('id="kkUserMenu"');
        $this->assertResponseContains('<div class="kk-user-name text-truncate">Alex Example</div>');
        $this->assertResponseContains('<div class="kk-user-email text-truncate">alex@example.test</div>');
        $this->assertSame(1, substr_count($this->body(), '<h6 class="dropdown-header">Applications</h6>'));
        $this->assertResponseContains('href="https://app.example.test/" aria-current="page"');
        $this->assertResponseContains('Your account');
        $this->assertResponseContains('action="/sign-out"');
    }

    public function testSignOutIsAFormPostWithoutScript(): void
    {
        Configure::write('TestApp.userMenuItems', [
            ['type' => 'sign_out', 'label' => 'Sign out', 'url' => '/sign-out'],
        ]);
        $this->get('/');

        $this->assertMatchesRegularExpression(
            '#<form method="post"[^>]* class="m-0" action="/sign-out">.*?'
            . '<button type="submit" class="dropdown-item d-flex align-items-center gap-2">'
            . '<i class="bi bi-box-arrow-right text-secondary"></i>Sign out</button></form>#s',
            $this->body(),
        );
        $this->assertResponseContains('name="_csrfToken"');
    }

    /**
     * A strict Content-Security-Policy (`script-src 'self'`) blocks inline
     * event handlers, `javascript:` URLs and inline scripts, so no layout
     * may render them. JSON data blocks are not run and stay allowed.
     *
     * @return void
     */
    public function testNoLayoutRendersScriptAStrictContentSecurityPolicyBlocks(): void
    {
        Configure::write('TestApp.userMenuItems', [
            ['type' => 'header', 'label' => 'Alex Example', 'detail' => 'alex@example.test'],
            ['type' => 'app', 'label' => 'Demo App', 'url' => 'https://app.example.test/', 'current' => true],
            ['type' => 'link', 'label' => 'Your account', 'url' => 'https://id.example.test/account'],
            ['type' => 'sign_out', 'label' => 'Sign out', 'url' => '/sign-out'],
        ]);
        Configure::write('debug', false);
        try {
            foreach (['/', '/reports', '/dashboard/banner', '/dashboard/sign-in', '/dashboard/fragment', '/no-such-page'] as $url) {
                $this->get($url);
                $body = $this->body();

                $this->assertDoesNotMatchRegularExpression('#<[^>]*\\son[a-z]+\\s*=#i', $body, $url . ' has an inline event handler');
                $this->assertStringNotContainsStringIgnoringCase('javascript:', $body, $url . ' has a javascript: URL');
                preg_match_all('#<script\\b([^>]*)>#i', $body, $scripts);
                foreach ($scripts[1] as $attributes) {
                    $this->assertMatchesRegularExpression(
                        '#\\ssrc=|\\stype="application/(ld\\+)?json"#',
                        $attributes,
                        $url . ' has an inline script',
                    );
                }
            }
        } finally {
            Configure::write('debug', true);
        }
    }

    public function testTopbarEndRendersBeforeTheUserMenu(): void
    {
        Configure::write('Ui.elements', ['default' => ['topbarEnd' => ['demo_topbar_end']]]);
        Configure::write('TestApp.userMenuItems', [
            ['type' => 'sign_out', 'label' => 'Sign out', 'url' => '/sign-out'],
        ]);
        $this->get('/');

        $body = $this->body();
        $this->assertStringContainsString('<div class="kk-topbar-end"><form method="get" action="/"', $body);
        $this->assertGreaterThan(strpos($body, '<div class="kk-topbar-right">'), strpos($body, 'id="demo-topbar-end"'));
        $this->assertLessThan(strpos($body, 'id="kkUserMenu"'), strpos($body, 'id="demo-topbar-end"'));
        $this->assertLessThan(strpos($body, '</header>'), strpos($body, 'id="demo-topbar-end"'));
    }

    public function testTopbarEndRendersWithoutAUserMenu(): void
    {
        Configure::write('Ui.elements', ['default' => ['topbarEnd' => ['demo_topbar_end']]]);
        $this->get('/');

        $this->assertResponseContains('<div class="kk-topbar-right">');
        $this->assertResponseContains('id="demo-topbar-end"');
        $this->assertResponseNotContains('kkUserMenu');
    }

    public function testTopbarEndIsLeftOutWhenNotConfigured(): void
    {
        $this->get('/');

        $this->assertResponseNotContains('kk-topbar-end');
        $this->assertResponseNotContains('kk-topbar-right');
    }

    public function testUserMenuIsLeftOutWithoutEntries(): void
    {
        $this->get('/');

        $this->assertResponseNotContains('kkUserMenu');
    }

    public function testBannerBlockShowsUnderTheTopBar(): void
    {
        $this->get('/dashboard/banner');

        $body = $this->body();
        $this->assertStringContainsString('<div class="kk-banner">', $body);
        $this->assertLessThan(strpos($body, '<div class="p-3">'), strpos($body, 'id="demo-banner"'));
        $this->assertGreaterThan(strpos($body, '</header>'), strpos($body, 'id="demo-banner"'));
    }

    public function testApplicationElementsRenderInTheirPlaces(): void
    {
        Configure::write('Ui.elements', ['default' => ['head' => ['demo_head'], 'bodyEnd' => ['demo_body_end']]]);
        $this->get('/');

        $body = $this->body();
        $this->assertLessThan(strpos($body, '</head>'), strpos($body, '<meta name="demo-head"'));
        $this->assertGreaterThan(strpos($body, 'app-chrome.js'), strpos($body, 'id="demo-body-end"'));
    }

    public function testSettingsShapeTheFrame(): void
    {
        Configure::write('Ui', [
            'htmlLang' => 'sv',
            'bodyClass' => 'demo-app',
            'titleSuffix' => ' | Demo',
            'storageKeyPrefix' => 'demo',
        ]);
        UiPlugin::applyDefaults();
        $this->get('/');

        $this->assertResponseContains('<html lang="sv">');
        $this->assertResponseContains('<body class="kk-app demo-app">');
        $this->assertResponseContains('<title>[DEV] Dashboard | Demo</title>');
        $this->assertResponseContains('data-storage-key-prefix="demo"');
    }

    public function testAuthLayoutShowsTheSignInCard(): void
    {
        Settings::set(SettingsPlugin::APPLICATION_ORGANIZATION_NAME, 'Example Ltd');
        $this->get('/dashboard/sign-in');

        $this->assertResponseOk();
        $this->assertResponseContains('<body class="kk-auth">');
        $this->assertResponseContains('<div class="auth-mark"><i class="bi bi-rocket"></i></div>');
        $this->assertResponseContains('<h1 class="h5 mt-3 mb-1">Demo App</h1>');
        $this->assertResponseContains('<p class="text-body-secondary small mb-0">Example Ltd</p>');
        $this->assertResponseContains('<title>[DEV] Sign in · Demo App</title>');
        $this->assertResponseNotContains('mainMenu');
        $this->assertResponseNotContains('app-chrome.js');
    }

    public function testTheNameComesFromTheSettingsAndUiAppNameIsIgnored(): void
    {
        Settings::set(SettingsPlugin::APPLICATION_NAME, 'Sample Portal');
        Configure::write('Ui.appName', 'Old Name');
        Configure::write('Ui.organizationName', 'Old Ltd');

        $this->get('/');
        $this->assertResponseContains('<span class="kk-brand-name">Sample Portal</span>');
        $this->assertResponseContains('<title>[DEV] Dashboard · Sample Portal</title>');

        $this->get('/dashboard/sign-in');
        $this->assertResponseContains('<h1 class="h5 mt-3 mb-1">Sample Portal</h1>');
        $this->assertResponseNotContains('Old Name');
        $this->assertResponseNotContains('Old Ltd');
    }

    public function testAuthLayoutLeavesOutAnEmptyOrganizationName(): void
    {
        $this->get('/dashboard/sign-in');

        $this->assertResponseNotContains('text-body-secondary small');
    }

    public function testErrorLayoutShowsTheErrorAndTheWayBack(): void
    {
        // Debug mode shows CakePHP's developer page instead.
        Configure::write('debug', false);
        try {
            $this->get('/no-such-page');
        } finally {
            Configure::write('debug', true);
        }

        $this->assertResponseCode(404);
        $this->assertResponseContains('<body class="kk-auth">');
        $this->assertResponseContains('Something went wrong');
        $this->assertResponseContains('<title>Error · Demo App</title>');
        $this->assertResponseContains('<a href="/" class="btn btn-outline-secondary btn-sm">');
        $this->assertResponseContains('Back to the dashboard');
        $this->assertResponseNotContains('mainMenu');
    }

    public function testAjaxLayoutRendersTheContentAlone(): void
    {
        $this->get('/dashboard/fragment');

        $this->assertResponseOk();
        $this->assertSame('<p id="fragment">A fragment.</p>', trim($this->body()));
    }

    public function testFinnishLocaleTranslatesTheFrame(): void
    {
        I18n::setLocale('fi_FI');
        Configure::write('TestApp.userMenuItems', [['type' => 'app', 'label' => 'Other', 'url' => '/', 'current' => false]]);
        $this->get('/');

        $this->assertResponseContains('<html lang="fi">');
        $this->assertResponseContains('aria-label="Avaa valikko"');
        $this->assertResponseContains('aria-label="Päävalikko"');
        $this->assertResponseContains('data-label-unpin="Irrota valikko"');
        $this->assertResponseContains('Sovellukset');
        $this->assertResponseNotContains('Open menu');
    }

    public function testLocaleSettingOverridesTheApplicationsLocale(): void
    {
        Configure::write('Ui.locale', 'fi');
        // The texts are read once per locale; start from none read.
        I18n::clear();
        UiPlugin::loadTranslations();
        $this->get('/');

        $this->assertResponseContains('aria-label="Avaa valikko"');
    }

    /**
     * @return string
     */
    private function body(): string
    {
        $this->assertNotNull($this->_response);

        return (string)$this->_response->getBody();
    }
}
