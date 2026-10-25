<?php
declare(strict_types=1);

namespace RockSoftware\Ui\View\Helper;

use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\I18n\I18n;
use Cake\View\Helper;
use RockSoftware\Ui\UiPlugin;
use RockSoftware\Ui\View\MainMenuInterface;

/**
 * What the frame's layouts and elements read: the application's settings
 * (`UiPlugin::DEFAULTS`), its main menu and the user menu. The layouts load
 * it themselves; an application does not need to.
 *
 * @extends \Cake\View\Helper<\Cake\View\View>
 */
class UiHelper extends Helper
{
    /** The styles every layout loads, from the plugin's webroot. */
    private const BASE_CSS = ['bootstrap.min', 'bootstrap-icons.min', 'app-chrome'];

    /** The styles of each optional component. */
    private const COMPONENT_CSS = [
        'breadcrumb' => ['kk-breadcrumb'],
        'modal' => ['kk-modal'],
        'listTable' => ['kk-list-table'],
    ];

    /** The scripts of each optional component, after Bootstrap and the chrome. */
    private const COMPONENT_SCRIPTS = [
        'modal' => ['components/kk-modal', 'components/kk-modal-markup'],
        'listTable' => ['components/kk-list-table'],
    ];

    /**
     * @return string
     */
    public function appName(): string
    {
        return (string)UiPlugin::setting('appName');
    }

    /**
     * @return string
     */
    public function brandIcon(): string
    {
        return (string)UiPlugin::setting('brandIcon');
    }

    /**
     * @return string|null
     */
    public function organizationName(): ?string
    {
        $name = UiPlugin::setting('organizationName');

        return is_string($name) && $name !== '' ? $name : null;
    }

    /**
     * @return array<string, mixed>|string
     */
    public function homeUrl(): array|string
    {
        $url = UiPlugin::setting('homeUrl');

        return is_array($url) ? $url : (string)$url;
    }

    /**
     * The `<html lang>`: the setting, or the language of the locale.
     *
     * @return string
     */
    public function htmlLang(): string
    {
        $lang = UiPlugin::setting('htmlLang');
        if (is_string($lang) && $lang !== '') {
            return $lang;
        }

        return strtolower(substr(I18n::getLocale(), 0, 2));
    }

    /**
     * The frame's body class followed by the application's own.
     *
     * @param string $frameClass The layout's class (`kk-app`, `kk-auth`).
     * @return string
     */
    public function bodyClass(string $frameClass): string
    {
        $own = UiPlugin::setting('bodyClass');

        return trim($frameClass . ' ' . (is_string($own) ? $own : ''));
    }

    /**
     * The `<title>` text, unescaped: the page's title and the suffix, marked
     * `[DEV]` in debug mode when asked.
     *
     * @param string $pageTitle The `title` block.
     * @param bool $isDevMarked Whether debug mode prefixes `[DEV] `.
     * @return string
     */
    public function title(string $pageTitle, bool $isDevMarked = true): string
    {
        $suffix = UiPlugin::setting('titleSuffix');
        if (!is_string($suffix)) {
            $suffix = ' · ' . $this->appName();
        }

        return ($isDevMarked && Configure::read('debug') ? '[DEV] ' : '') . $pageTitle . $suffix;
    }

    /**
     * @return string
     */
    public function storageKeyPrefix(): string
    {
        return (string)UiPlugin::setting('storageKeyPrefix');
    }

    /**
     * Whether the application switched an optional component on.
     *
     * @param string $component `breadcrumb`, `modal` or `listTable`.
     * @return bool
     */
    public function isEnabled(string $component): bool
    {
        $components = (array)UiPlugin::setting('components');

        return ($components[$component] ?? false) === true;
    }

    /**
     * The plugin styles a layout loads, as `HtmlHelper::css()` names.
     *
     * @param bool $includesComponents Whether the optional components' styles are included.
     * @return list<string>
     */
    public function css(bool $includesComponents = true): array
    {
        $names = self::BASE_CSS;
        if ($includesComponents) {
            foreach (self::COMPONENT_CSS as $component => $files) {
                if ($this->isEnabled($component)) {
                    array_push($names, ...$files);
                }
            }
        }

        return array_map(fn(string $name): string => UiPlugin::NAME . '.' . $name, $names);
    }

    /**
     * The plugin scripts a layout loads, as `HtmlHelper::script()` names.
     *
     * @param bool $includesChrome Whether the drawer's script is included.
     * @return list<string>
     */
    public function scripts(bool $includesChrome = true): array
    {
        $names = ['bootstrap.bundle.min'];
        if ($includesChrome) {
            $names[] = 'app-chrome';
        }
        foreach (self::COMPONENT_SCRIPTS as $component => $files) {
            if ($this->isEnabled($component)) {
                array_push($names, ...$files);
            }
        }

        return array_map(fn(string $name): string => UiPlugin::NAME . '.' . $name, $names);
    }

    /**
     * The texts of the optional scripted components in the current language,
     * for the `kk-ui-texts` block they read; empty when none is on.
     *
     * @return array<string, string>
     */
    public function componentTexts(): array
    {
        $texts = [];
        if ($this->isEnabled('modal')) {
            $texts += [
                'modalMove' => __d('rock_software_ui', 'Move the window'),
                'modalClose' => __d('rock_software_ui', 'Close'),
            ];
        }
        if ($this->isEnabled('listTable')) {
            $texts += [
                'listEmpty' => __d('rock_software_ui', 'No rows match the filters.'),
                'listAll' => __d('rock_software_ui', 'All'),
                'listNoneMeansAll' => __d('rock_software_ui', 'None selected = all'),
                'listFrom' => __d('rock_software_ui', 'From'),
                'listTo' => __d('rock_software_ui', 'To'),
                'listOrder' => __d('rock_software_ui', 'Order'),
                'listAscending' => __d('rock_software_ui', 'Ascending'),
                'listDescending' => __d('rock_software_ui', 'Descending'),
                'listSelectRow' => __d('rock_software_ui', 'Select row'),
                'listSelectAll' => __d('rock_software_ui', 'Select all'),
                'locale' => $this->htmlLang(),
            ];
        }

        return $texts;
    }

    /**
     * Renders the application's elements configured for one place of a layout.
     *
     * @param string $layout `default`, `auth` or `error`.
     * @param string $place `head`, `topbarEnd`, `banner` or `bodyEnd`.
     * @return string
     */
    public function elements(string $layout, string $place): string
    {
        $elements = (array)UiPlugin::setting('elements');
        $names = (array)($elements[$layout][$place] ?? []);
        $html = '';
        foreach ($names as $name) {
            $html .= $this->getView()->element((string)$name);
        }

        return $html;
    }

    /**
     * The main menu's groups; none without a `mainMenu` view variable.
     *
     * @return array<int, array{title: string, icon: string, url?: array<string, mixed>, items?: array<int, array{title: string, url: array<string, mixed>, badge?: string, badgeLabel?: string}>}>
     */
    public function menuGroups(): array
    {
        $menu = $this->getView()->get('mainMenu');

        return $menu instanceof MainMenuInterface ? $menu->groups() : [];
    }

    /**
     * Whether a menu group is one direct link: it has a `url` and no items.
     *
     * @param array<string, mixed> $group A menu group.
     * @return bool
     */
    public function isLinkGroup(array $group): bool
    {
        return isset($group['url']) && empty($group['items']);
    }

    /**
     * The count an item's badge shows; 0 shows none.
     *
     * @param array<string, mixed> $item A menu item.
     * @return int
     */
    public function badgeCount(array $item): int
    {
        if (!isset($item['badge'])) {
            return 0;
        }
        $menu = $this->getView()->get('mainMenu');
        $counts = $menu instanceof MainMenuInterface ? $menu->badgeCounts() : [];

        return (int)($counts[$item['badge']] ?? 0);
    }

    /**
     * Whether a menu item, or a group that is one link, is the page being
     * shown: its URL's controller and plugin are the request's.
     *
     * @param array<string, mixed> $item A menu item or link group.
     * @return bool
     */
    public function isActive(array $item): bool
    {
        $request = $this->getView()->getRequest();
        $url = (array)$item['url'];

        return ($url['controller'] ?? null) === (string)$request->getParam('controller')
            && ($url['plugin'] ?? null) === $request->getParam('plugin');
    }

    /**
     * The user menu's entries: the `userMenuItems` view variable when the
     * application sets one, otherwise the sign-in plugin's `UserMenu` helper
     * when it is loaded. Entry types: `header` (`label`, `detail`), `app`
     * (`label`, `url`, `current`), `link` (`label`, `url`) and `sign_out`
     * (`label`, `url`, posted).
     *
     * @return array<array<string, mixed>>
     */
    public function userMenuItems(): array
    {
        $view = $this->getView();
        $items = $view->get('userMenuItems');
        if (is_array($items)) {
            return $items;
        }
        if (!Plugin::isLoaded('RockSoftware/Users')) {
            return [];
        }
        $helper = $view->loadHelper('RockSoftware/Users.UserMenu');

        return method_exists($helper, 'items') ? (array)$helper->items() : [];
    }
}
