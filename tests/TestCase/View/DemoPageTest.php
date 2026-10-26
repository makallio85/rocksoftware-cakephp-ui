<?php
declare(strict_types=1);

namespace RockSoftware\Ui\Test\TestCase\View;

use Cake\Core\Configure;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use RockSoftware\Ui\UiPlugin;
use TestApp\Application;

/**
 * The components page of the test application: every shared piece renders,
 * and nothing on the page needs inline script or inline style, so it works
 * under the strict Content-Security-Policy the test application sends.
 */
class DemoPageTest extends TestCase
{
    use IntegrationTestTrait;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        Configure::write('Ui', []);
        UiPlugin::applyDefaults();
    }

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        Configure::delete('Ui');
        UiPlugin::applyDefaults();
        parent::tearDown();
    }

    public function testEveryComponentRenders(): void
    {
        $this->get('/components');

        $this->assertResponseOk();
        $this->assertResponseContains('<div class="kk-page-header">');
        $this->assertResponseContains('<span class="kk-badge kk-badge-ok">Delivered</span>');
        $this->assertResponseContains('<div class="dropdown kk-kebab">');
        $this->assertResponseContains('<ul class="pagination kk-pagination">');
        $this->assertResponseContains('Showing 1–4 of 47');
        $this->assertResponseContains('data-confirm="Messages already sent stay in the log. This cannot be undone."');
        $this->assertResponseContains('<div class="kk-empty-state">');
        $this->assertResponseContains('<div class="form-text" id="sender-name-help">');
        $this->assertResponseContains('<div class="invalid-feedback d-block" id="reply-address-error">');
        $this->assertResponseContains('nav-tabs-scrollable');
        $this->assertResponseContains('26.10.2026 08.14');
        $this->assertResponseContains('<a class="kk-sort desc" href="/components?sort=sent_at&amp;direction=asc">');
    }

    public function testThePageWorksUnderAStrictContentSecurityPolicy(): void
    {
        $this->get('/components');

        $this->assertHeader('Content-Security-Policy', Application::CONTENT_SECURITY_POLICY);
        $this->assertNotNull($this->_response);
        $body = (string)$this->_response->getBody();
        // Event handler attributes and javascript: URLs.
        $this->assertDoesNotMatchRegularExpression('/<[^>]+\son[a-z]+\s*=/i', $body);
        $this->assertStringNotContainsString('javascript:', $body);
        // Inline style.
        $this->assertDoesNotMatchRegularExpression('/<[^>]+\sstyle\s*=/i', $body);
        $this->assertStringNotContainsString('<style', $body);
        // Every <script> is a file or a JSON data block.
        preg_match_all('/<script\b([^>]*)>/i', $body, $scripts);
        foreach ($scripts[1] as $attributes) {
            $this->assertMatchesRegularExpression('/\ssrc="|type="application\/json"/', $attributes);
        }
    }

    public function testConfirmedDeletePostsAndRedirects(): void
    {
        $this->enableCsrfToken();
        $this->post('/components/delete/7');

        $this->assertRedirect('/components');
        $this->assertFlashMessage('Message 7 deleted (demo: nothing was stored).');
    }
}
