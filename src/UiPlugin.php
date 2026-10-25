<?php
declare(strict_types=1);

namespace RockSoftware\Ui;

use Cake\Core\BasePlugin;
use Cake\Core\Configure;
use Cake\Core\PluginApplicationInterface;
use Cake\I18n\I18n;
use Cake\I18n\Package;
use Cake\I18n\Parser\PoFileParser;

/**
 * The shared screen frame: the layouts (`default`, `auth`, `error`, `ajax`),
 * the top bar, the main menu drawer, the user menu, flash messages and their
 * styles and scripts. An application uses it as its view theme:
 *
 *     // src/View/AppView.php
 *     $this->setTheme('RockSoftware/Ui');
 *
 * The application supplies its name, icon, language and main menu (a
 * `View\MainMenuInterface` in the `mainMenu` view variable); every setting
 * below has a default in `DEFAULTS`.
 */
class UiPlugin extends BasePlugin
{
    /**
     * Every setting and its default. An application overrides any of them in
     * `Configure::write('Ui', [...])` (usually `config/app.php`).
     *
     * - `appName`: the application's name, in the top bar, on the sign-in card
     *   and in the page title.
     * - `brandIcon`: the Bootstrap Icons class of the brand mark (`bi bi-…`).
     * - `organizationName`: the line under the application's name on the
     *   sign-in card; null for none.
     * - `htmlLang`: the `<html lang>`; null takes it from the locale.
     * - `bodyClass`: classes the application adds to `<body>`, beside the
     *   frame's own.
     * - `titleSuffix`: what follows the page title; null for ` · <appName>`.
     * - `homeUrl`: where the brand and the error page's back link go.
     * - `storageKeyPrefix`: the prefix of the browser storage keys that keep
     *   the menu's pin and its collapsed groups.
     * - `components`: the optional components to load, each `true` or
     *   `false`: `breadcrumb` (the trail in the top bar, from the
     *   `breadcrumb` view variable), `modal` (`window.KkModal`),
     *   `listTable` (`window.KkListTable`), `confirm` (the confirmation
     *   dialog of buttons with `data-confirm`; also loaded on any page that
     *   renders `UiHelper::postButton()` with a `confirm` message) and
     *   `toast` (`window.appBanner`, short messages in a corner).
     * - `elements`: the application's own elements the layouts render, by
     *   layout (`default`, `auth`, `error`) and place: `head` (after the
     *   frame's styles), `topbarEnd` (in the top bar before the user menu,
     *   `default` only), `banner` (under the top bar, `default` only) and
     *   `bodyEnd` (after the frame's scripts, before the `script` block).
     * - `locale`: the language of the frame's texts when it differs from the
     *   application's locale (e.g. `fi`); null follows the application.
     * - `displayTimezone`: the time zone `UiHelper::dateTime()` shows moments
     *   in.
     *
     * @var array<string, mixed>
     */
    public const DEFAULTS = [
        'appName' => 'Application',
        'brandIcon' => 'bi bi-grid-1x2',
        'organizationName' => null,
        'htmlLang' => null,
        'bodyClass' => null,
        'titleSuffix' => null,
        'homeUrl' => '/',
        'storageKeyPrefix' => 'ui',
        'components' => [
            'breadcrumb' => false,
            'modal' => false,
            'listTable' => false,
            'confirm' => false,
            'toast' => false,
        ],
        'elements' => [],
        'locale' => null,
        'displayTimezone' => 'Europe/Helsinki',
    ];

    /** The settings key under `Configure`. */
    public const CONFIG_KEY = 'Ui';

    /** The translation domain of every text the frame shows. */
    public const TRANSLATION_DOMAIN = 'rock_software_ui';

    /** The plugin's name, which is also the theme an application sets. */
    public const NAME = 'RockSoftware/Ui';

    protected ?string $name = self::NAME;

    protected bool $middlewareEnabled = false;

    protected bool $routesEnabled = false;

    protected bool $consoleEnabled = false;

    protected bool $bootstrapEnabled = true;

    /**
     * Fills in every setting the application left out and registers the texts.
     *
     * @param \Cake\Core\PluginApplicationInterface $app The application.
     * @return void
     */
    public function bootstrap(PluginApplicationInterface $app): void
    {
        self::applyDefaults();
        self::loadTranslations();
    }

    /**
     * @return void
     */
    public static function applyDefaults(): void
    {
        $settings = array_replace(self::DEFAULTS, (array)Configure::read(self::CONFIG_KEY, []));
        $settings['components'] = array_replace(self::DEFAULTS['components'], (array)$settings['components']);
        Configure::write(self::CONFIG_KEY, $settings);
    }

    /**
     * One setting, or its default when the application has not set it.
     *
     * @param string $key A key of `DEFAULTS`.
     * @return mixed
     */
    public static function setting(string $key): mixed
    {
        $settings = (array)Configure::read(self::CONFIG_KEY, []);

        return array_key_exists($key, $settings) ? $settings[$key] : (self::DEFAULTS[$key] ?? null);
    }

    /**
     * Registers the frame's texts (domain `rock_software_ui`) from the
     * plugin's own `resources/locales`. CakePHP finds a plugin's catalogue by
     * domain name only for plugins without a vendor prefix, so the plugin
     * registers it.
     *
     * @return void
     */
    public static function loadTranslations(): void
    {
        $path = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'locales'
            . DIRECTORY_SEPARATOR;
        I18n::config(self::TRANSLATION_DOMAIN, function (string $domain, string $locale) use ($path): Package {
            $fixed = self::setting('locale');
            $locale = is_string($fixed) && $fixed !== '' ? $fixed : $locale;
            $messages = [];
            foreach ([$locale, substr($locale, 0, 2)] as $folder) {
                $file = $path . $folder . DIRECTORY_SEPARATOR . self::TRANSLATION_DOMAIN . '.po';
                if (is_file($file)) {
                    $messages = (new PoFileParser())->parse($file);
                    break;
                }
            }

            return new Package('default', null, $messages);
        });
    }
}
