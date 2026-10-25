<?php
declare(strict_types=1);

namespace RockSoftware\Ui\Test\TestCase\View\Helper;

use Cake\Http\ServerRequest;
use Cake\TestSuite\TestCase;
use RockSoftware\Ui\View\Helper\FormHelper;
use TestApp\Form\DemoSettingsForm;
use TestApp\View\AppView;

/**
 * The opt-in form helper: the `help` option, Bootstrap's validation look and
 * no inline script.
 */
class FormHelperTest extends TestCase
{
    private FormHelper $form;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->form = new FormHelper(new AppView(new ServerRequest()));
    }

    public function testHelpRendersUnderTheFieldAndDescribesIt(): void
    {
        $this->form->create(new DemoSettingsForm());
        $html = $this->form->control('sender_name', ['help' => 'Shown to the <recipient>.']);

        $this->assertStringContainsString(
            '<div class="form-text" id="sender-name-help">Shown to the &lt;recipient&gt;.</div></div>',
            $html,
        );
        $this->assertStringContainsString('aria-describedby="sender-name-help"', $html);
        $this->assertStringNotContainsString(' help=', $html);
    }

    public function testHelpFollowsACustomId(): void
    {
        $this->form->create(new DemoSettingsForm());
        $html = $this->form->control('sender_name', ['id' => 'sender', 'help' => 'Shown to the recipient.']);

        $this->assertStringContainsString('id="sender-help"', $html);
        $this->assertStringContainsString('aria-describedby="sender-help"', $html);
    }

    public function testAFieldWithoutHelpIsAsInCore(): void
    {
        $this->form->create(new DemoSettingsForm());
        $html = $this->form->control('sender_name');

        $this->assertStringNotContainsString('form-text', $html);
        $this->assertStringNotContainsString('aria-describedby', $html);
        $this->assertStringContainsString('<div class="input text">', $html);
    }

    public function testAnErrorShowsInBootstrapsLookBeforeTheHelp(): void
    {
        $settings = new DemoSettingsForm();
        $settings->validate(['reply_address' => 'not-an-address']);
        $this->form->create($settings);
        $html = $this->form->control('reply_address', ['class' => 'form-control', 'help' => 'Replies go here.']);

        $this->assertStringContainsString('class="form-control is-invalid"', $html);
        $this->assertStringContainsString('aria-invalid="true"', $html);
        $this->assertStringContainsString('aria-describedby="reply-address-error reply-address-help"', $html);
        $this->assertStringContainsString(
            '<div class="invalid-feedback d-block" id="reply-address-error">Enter a valid email address.</div>'
            . '<div class="form-text" id="reply-address-help">Replies go here.</div>',
            $html,
        );
    }

    public function testRequiredFieldsCarryNoInlineScript(): void
    {
        $settings = new DemoSettingsForm();
        $settings->validate(['reply_address' => 'not-an-address']);
        $this->form->create($settings);
        $html = $this->form->control('reply_address');

        $this->assertStringContainsString('required="required"', $html);
        $this->assertStringNotContainsString('oninvalid', $html);
        $this->assertStringNotContainsString('oninput', $html);
    }

    public function testTheApplicationCanTurnTheBrowserMessageScriptBackOn(): void
    {
        $form = new FormHelper(new AppView(new ServerRequest()), ['autoSetCustomValidity' => true]);
        $settings = new DemoSettingsForm();
        $settings->validate(['reply_address' => 'not-an-address']);
        $form->create($settings);

        $this->assertStringContainsString('oninvalid', $form->control('reply_address'));
    }
}
