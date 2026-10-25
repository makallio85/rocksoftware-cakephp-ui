<?php
declare(strict_types=1);

namespace RockSoftware\Ui\View\Helper;

use Cake\View\Helper\FormHelper as CoreFormHelper;

/**
 * CakePHP's form helper with a `help` option and Bootstrap's validation look.
 * Opt-in: an application that wants it loads it as its `Form` helper, in
 * `AppView::initialize()`:
 *
 *     $this->addHelper('Form', ['className' => 'RockSoftware/Ui.Form']);
 *
 * - `help`: `$this->Form->control('name', ['help' => 'Shown to the
 *   recipient.'])` renders the text under the field as `div.form-text`,
 *   escaped, and points the field's `aria-describedby` at it. Core CakePHP
 *   has no such option and passes it through as an unseen `help="…"`
 *   attribute. A help text that needs markup is written in the template as
 *   its own `.form-text` instead.
 * - A field with a validation error gets Bootstrap's `is-invalid` and its
 *   message `.invalid-feedback`, so the field turns red and the message shows
 *   under it.
 * - No inline script: core CakePHP writes `oninvalid` / `oninput` handlers on
 *   required fields (`autoSetCustomValidity`), which a Content-Security-Policy
 *   of `script-src 'self'` refuses. They are off unless the application
 *   turns them on; the browser's own message shows instead.
 */
class FormHelper extends CoreFormHelper
{
    /**
     * @param array<string, mixed> $config The helper config.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        if (!array_key_exists('autoSetCustomValidity', $config)) {
            $this->setConfig('autoSetCustomValidity', false);
        }
        $this->setTemplates([
            'errorClass' => 'is-invalid',
            'help' => '<div class="form-text" id="{{id}}">{{content}}</div>',
            'error' => '<div class="invalid-feedback d-block" id="{{id}}">{{content}}</div>',
            'inputContainer' => '<div class="{{containerClass}} {{type}}{{required}}">{{content}}{{help}}</div>',
            'inputContainerError' =>
                '<div class="{{containerClass}} {{type}}{{required}} error">{{content}}{{error}}{{help}}</div>',
        ]);
        if (isset($config['templates']) && is_array($config['templates'])) {
            $this->setTemplates($config['templates']);
        }
    }

    /**
     * `FormHelper::control()` with the `help` option.
     *
     * @param string $fieldName The field.
     * @param array<string, mixed> $options The control's options, and `help`.
     * @return string
     */
    public function control(string $fieldName, array $options = []): string
    {
        $help = trim((string)($options['help'] ?? ''));
        unset($options['help']);
        $options['templateVars']['help'] ??= '';

        if ($help !== '') {
            $helpId = (isset($options['id']) && is_string($options['id']) ? $options['id'] : $this->_domId($fieldName))
                . '-help';
            $options['templateVars']['help'] = $this->formatTemplate('help', [
                'id' => $helpId,
                'content' => h($help),
            ]);
            $describedBy = [$helpId];
            if ($this->isFieldError($fieldName)) {
                array_unshift($describedBy, $this->_domId($fieldName) . '-error');
            }
            if (isset($options['aria-describedby']) && is_string($options['aria-describedby'])) {
                array_unshift($describedBy, $options['aria-describedby']);
            }
            $options['aria-describedby'] = implode(' ', array_unique($describedBy));
        }

        return parent::control($fieldName, $options);
    }
}
