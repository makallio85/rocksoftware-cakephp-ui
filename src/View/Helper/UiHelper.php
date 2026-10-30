<?php
declare(strict_types=1);

namespace RockSoftware\Ui\View\Helper;

use Cake\Chronos\ChronosDate;
use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\I18n\DateTime;
use Cake\I18n\I18n;
use Cake\View\Helper;
use DateTimeInterface;
use InvalidArgumentException;
use NumberFormatter;
use RockSoftware\Settings\Settings;
use RockSoftware\Settings\SettingsPlugin;
use RockSoftware\Ui\UiPlugin;
use RockSoftware\Ui\View\MainMenuInterface;
use Throwable;

/**
 * What the frame's layouts and elements read: the application's name and
 * the line under it (the settings plugin's `application.name` and
 * `application.organization_name`), the frame's settings
 * (`UiPlugin::DEFAULTS`), the main menu and the user menu; and the shared
 * in-page pieces a template uses: badges (`badge()`), a post button that asks
 * first (`postButton()`), the paginator's templates and the date and number
 * formats (`date()`, `dateTime()`, `number()`, `money()`, `percent()`). The layouts load
 * it themselves; a template that uses it before the layout runs loads it in
 * the application's `AppView` (`$this->addHelper('RockSoftware/Ui.Ui')`).
 *
 * @property \Cake\View\Helper\FormHelper $Form
 * @extends \Cake\View\Helper<\Cake\View\View>
 */
class UiHelper extends Helper
{
    /**
     * The tones a badge comes in. The application maps its own states to
     * them; the plugin knows no state.
     *
     * @var list<string>
     */
    public const BADGE_TONES = ['ok', 'pending', 'danger', 'muted', 'info', 'tag'];

    /**
     * Bootstrap 5 templates for CakePHP's `PaginatorHelper`: page links as
     * `.page-item` / `.page-link`, disabled ones as plain text (no inline
     * script), sort links with a caret. The `ui/pagination` element uses
     * them; an application sets them for its own sort links with
     * `$this->loadHelper('Paginator', ['templates' => UiHelper::PAGINATOR_TEMPLATES])`.
     *
     * @var array<string, string>
     */
    public const PAGINATOR_TEMPLATES = [
        'nextActive' => '<li class="page-item"><a class="page-link" rel="next" href="{{url}}">{{text}}</a></li>',
        'nextDisabled' => '<li class="page-item disabled"><span class="page-link">{{text}}</span></li>',
        'prevActive' => '<li class="page-item"><a class="page-link" rel="prev" href="{{url}}">{{text}}</a></li>',
        'prevDisabled' => '<li class="page-item disabled"><span class="page-link">{{text}}</span></li>',
        'counterRange' => '{{start}}–{{end}} / {{count}}',
        'counterPages' => '{{page}} / {{pages}}',
        'first' => '<li class="page-item kk-page-edge"><a class="page-link" href="{{url}}">{{text}}</a></li>',
        'last' => '<li class="page-item kk-page-edge"><a class="page-link" href="{{url}}">{{text}}</a></li>',
        'number' => '<li class="page-item"><a class="page-link" href="{{url}}">{{text}}</a></li>',
        'current' => '<li class="page-item active" aria-current="page"><span class="page-link">{{text}}</span></li>',
        'ellipsis' => '<li class="page-item disabled kk-page-edge"><span class="page-link">&hellip;</span></li>',
        'sort' => '<a class="kk-sort" href="{{url}}">{{text}}</a>',
        'sortAsc' => '<a class="kk-sort asc" href="{{url}}">{{text}}'
            . '<i class="bi bi-caret-up-fill" aria-hidden="true"></i></a>',
        'sortDesc' => '<a class="kk-sort desc" href="{{url}}">{{text}}'
            . '<i class="bi bi-caret-down-fill" aria-hidden="true"></i></a>',
        'sortAscLocked' => '<a class="kk-sort asc locked" href="{{url}}">{{text}}'
            . '<i class="bi bi-caret-up-fill" aria-hidden="true"></i></a>',
        'sortDescLocked' => '<a class="kk-sort desc locked" href="{{url}}">{{text}}'
            . '<i class="bi bi-caret-down-fill" aria-hidden="true"></i></a>',
    ];

    /** A day as shown: DD.MM.YYYY. */
    public const DATE_FORMAT = 'd.m.Y';

    /** A moment as shown: DD.MM.YYYY HH.mm. */
    public const DATE_TIME_FORMAT = 'd.m.Y H.i';

    /** The styles every layout loads, from the plugin's webroot. */
    private const BASE_CSS = ['bootstrap.min', 'bootstrap-icons.min', 'app-chrome', 'kk-components'];

    /** The styles of each optional component; a file two components share loads once. */
    private const COMPONENT_CSS = [
        'breadcrumb' => ['kk-breadcrumb'],
        'modal' => ['kk-modal'],
        'listTable' => ['kk-list-table', 'kk-date-time-picker'],
        'dateTimePicker' => ['kk-date-time-picker'],
        'fileDrop' => ['kk-file-drop'],
        'confirm' => ['kk-confirm'],
        'toast' => ['kk-toast'],
    ];

    /**
     * The scripts of each optional component, after Bootstrap and the chrome,
     * in this order; a file two components share loads once. The Vue
     * components are one precompiled script (`kk-vue-components`), after
     * Vue's runtime when the plugin supplies it.
     */
    private const COMPONENT_SCRIPTS = [
        'vue' => ['vue.runtime.global.prod'],
        'modal' => ['components/kk-vue-components', 'components/kk-modal-markup'],
        'listTable' => ['components/kk-vue-components'],
        'dateTimePicker' => ['components/kk-vue-components'],
        'fileDrop' => ['components/kk-vue-components'],
        'confirm' => ['components/kk-confirm'],
        'toast' => ['components/kk-toast'],
        'expandToggle' => ['components/kk-expand-toggle'],
        'format' => ['kk-format'],
    ];

    /** The components whose scripts read the page's locale and time zone. */
    private const LOCALE_COMPONENTS = ['listTable', 'dateTimePicker', 'fileDrop', 'format'];

    /** A day without a time: YYYY-MM-DD. */
    private const DATE_ONLY = '/^\d{4}-\d{2}-\d{2}$/';

    /**
     * @var array<array-key, array<string, mixed>|string>
     */
    protected array $helpers = ['Form'];

    /**
     * The components a template on this page needs although the application
     * did not switch them on, such as `confirm` for a `postButton()` that
     * asks first. The layout, rendered after the template, loads them.
     *
     * @var array<string, true>
     */
    private array $requiredComponents = [];

    /**
     * The application's name: the setting `application.name`.
     *
     * @return string
     */
    public function appName(): string
    {
        return (string)Settings::get(SettingsPlugin::APPLICATION_NAME);
    }

    /**
     * @return string
     */
    public function brandIcon(): string
    {
        return (string)UiPlugin::setting('brandIcon');
    }

    /**
     * The line under the application's name on the sign-in card: the setting
     * `application.organization_name`, or null when it is empty.
     *
     * @return string|null
     */
    public function organizationName(): ?string
    {
        $name = Settings::get(SettingsPlugin::APPLICATION_ORGANIZATION_NAME);

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

        return ($components[$component] ?? false) === true || isset($this->requiredComponents[$component]);
    }

    /**
     * Loads an optional component on this page, whatever the setting: a
     * template that renders something needing it calls this before the
     * layout runs.
     *
     * @param string $component A key of `UiPlugin::DEFAULTS['components']`.
     * @return void
     */
    public function requireComponent(string $component): void
    {
        $this->requiredComponents[$component] = true;
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

        return array_map(fn(string $name): string => UiPlugin::NAME . '.' . $name, array_values(array_unique($names)));
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

        return array_map(fn(string $name): string => UiPlugin::NAME . '.' . $name, array_values(array_unique($names)));
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
                // The pager's {start}, {end} and {count} are the script's placeholders.
                'pagerShowing' => str_replace(
                    ['{{start}}', '{{end}}', '{{count}}'],
                    ['{start}', '{end}', '{count}'],
                    __d('rock_software_ui', 'Showing {{start}}–{{end}} of {{count}}'),
                ),
                'pagerPrevious' => __d('rock_software_ui', 'Previous page'),
                'pagerNext' => __d('rock_software_ui', 'Next page'),
                'pagerPages' => __d('rock_software_ui', 'Pages'),
            ];
        }
        if ($this->isEnabled('listTable') || $this->isEnabled('dateTimePicker')) {
            $texts += [
                'pickerDateTime' => __d('rock_software_ui', 'Pick a date and time'),
                'pickerDate' => __d('rock_software_ui', 'Pick a date'),
                'pickerTime' => __d('rock_software_ui', 'Pick a time'),
                'pickerMonth' => __d('rock_software_ui', 'Pick a month'),
                'pickerTimeList' => __d('rock_software_ui', 'Time'),
                'pickerClear' => __d('rock_software_ui', 'Clear'),
            ];
        }
        if ($this->isEnabled('fileDrop')) {
            $texts += [
                'fileDropText' => __d('rock_software_ui', 'Drop a file here or click to choose'),
                'fileDropRemove' => __d('rock_software_ui', 'Remove {name}'),
                'fileDropWrongType' => __d('rock_software_ui', '{name} is not a file type this field takes.'),
                'fileDropTooLarge' => __d('rock_software_ui', '{name} is larger than {size}.'),
            ];
        }
        foreach (self::LOCALE_COMPONENTS as $component) {
            if ($this->isEnabled($component)) {
                $texts += [
                    'locale' => $this->htmlLang(),
                    'numberLocale' => str_replace('_', '-', $this->numberLocale()),
                    'timeZone' => $this->displayTimezone(),
                ];
                break;
            }
        }
        if ($this->isEnabled('confirm')) {
            $texts += [
                'confirmTitle' => __d('rock_software_ui', 'Are you sure?'),
                'confirmButton' => __d('rock_software_ui', 'Confirm'),
                'confirmCancel' => __d('rock_software_ui', 'Cancel'),
            ];
        }
        if ($this->isEnabled('toast')) {
            $texts += [
                'toastClose' => __d('rock_software_ui', 'Close'),
            ];
        }
        if ($this->isEnabled('expandToggle')) {
            $texts += [
                'expandToggleShow' => __d('rock_software_ui', 'Show more'),
                'expandToggleHide' => __d('rock_software_ui', 'Show less'),
            ];
        }

        return $texts;
    }

    /**
     * A badge: a short status or label in one of `BADGE_TONES`, with its
     * colours set by the plugin (`.kk-badge-<tone>`).
     *
     * Options: `title` (a tooltip), `class` (more classes), `escape` (false
     * when `$text` is markup already escaped).
     *
     * @param string $text What the badge says.
     * @param string $tone `ok`, `pending`, `danger`, `muted`, `info` or `tag`.
     * @param array<string, mixed> $options See above.
     * @return string
     * @throws \InvalidArgumentException For a tone not in `BADGE_TONES`.
     */
    public function badge(string $text, string $tone = 'muted', array $options = []): string
    {
        if (!in_array($tone, self::BADGE_TONES, true)) {
            throw new InvalidArgumentException(sprintf(
                'Unknown badge tone "%s"; use one of %s.',
                $tone,
                implode(', ', self::BADGE_TONES),
            ));
        }
        $class = trim('kk-badge kk-badge-' . $tone . ' ' . (string)($options['class'] ?? ''));
        $title = isset($options['title']) ? ' title="' . h((string)$options['title']) . '"' : '';
        $content = ($options['escape'] ?? true) === false ? $text : h($text);

        return '<span class="' . h($class) . '"' . $title . '>' . $content . '</span>';
    }

    /**
     * `FormHelper::postButton()` that asks before it posts, without inline
     * script: the message goes into `data-confirm` and the `confirm`
     * component's script shows the dialog. Rendering one loads that
     * component on the page.
     *
     * Options besides those of `postButton()`:
     * - `confirm`: the question; without one the button posts at once;
     * - `confirmTitle`: the dialog's heading (default: Are you sure?);
     * - `confirmButton`: the label of the button that goes ahead (default: Confirm);
     * - `confirmTone`: `danger` (the default) or `primary`.
     * The form is inline (`d-inline`) unless `form.class` says otherwise.
     *
     * @param string $title The button's text, escaped unless `escapeTitle` is false.
     * @param array<string, mixed>|string $url Where the form posts.
     * @param array<string, mixed> $options See above.
     * @return string
     */
    public function postButton(string $title, array|string $url, array $options = []): string
    {
        $attributes = [
            'confirm' => 'data-confirm',
            'confirmTitle' => 'data-confirm-title',
            'confirmButton' => 'data-confirm-button',
            'confirmTone' => 'data-confirm-tone',
        ];
        $isConfirmed = isset($options['confirm']) && (string)$options['confirm'] !== '';
        foreach ($attributes as $option => $attribute) {
            if ($isConfirmed && isset($options[$option]) && (string)$options[$option] !== '') {
                $options[$attribute] = (string)$options[$option];
            }
            unset($options[$option]);
        }
        $form = isset($options['form']) && is_array($options['form']) ? $options['form'] : [];
        $form += ['class' => 'd-inline'];
        $options['form'] = $form;
        $options += ['type' => 'submit'];
        if ($isConfirmed) {
            $this->requireComponent('confirm');
        }

        return $this->Form->postButton($title, $url, $options);
    }

    /**
     * A day as DD.MM.YYYY. A calendar day (a `Date`, a `YYYY-MM-DD` string)
     * shows as it is; a moment shows as its day in the display time zone.
     *
     * @param \DateTimeInterface|\Cake\Chronos\ChronosDate|string|int|null $value The day or moment.
     * @param string $empty What a missing or unreadable value shows.
     * @return string
     */
    public function date(DateTimeInterface|ChronosDate|string|int|null $value, string $empty = '—'): string
    {
        if ($value instanceof ChronosDate) {
            return $value->format(self::DATE_FORMAT);
        }
        if (is_string($value) && preg_match(self::DATE_ONLY, $value) === 1) {
            [$year, $month, $day] = explode('-', $value);

            return $day . '.' . $month . '.' . $year;
        }
        $moment = $this->moment($value);

        return $moment === null ? $empty : $moment->format(self::DATE_FORMAT);
    }

    /**
     * A moment as DD.MM.YYYY HH.mm in the display time zone (the
     * `displayTimezone` setting). A string without a zone is read in PHP's
     * default zone, as the database layer reads it.
     *
     * @param \DateTimeInterface|\Cake\Chronos\ChronosDate|string|int|null $value The moment (an int is a Unix timestamp).
     * @param string $empty What a missing or unreadable value shows.
     * @return string
     */
    public function dateTime(DateTimeInterface|ChronosDate|string|int|null $value, string $empty = '—'): string
    {
        if ($value instanceof ChronosDate) {
            return $value->format(self::DATE_FORMAT);
        }
        $moment = $this->moment($value);

        return $moment === null ? $empty : $moment->format(self::DATE_TIME_FORMAT);
    }

    /**
     * A number in the language of the page: `1 842,30` in Finnish,
     * `1,842.30` in English.
     *
     * @param string|float|int|null $value A number or a decimal string (`'1842.30'`).
     * @param int $decimals Digits after the decimal separator.
     * @param string $empty What a missing or unreadable value shows.
     * @return string
     */
    public function number(string|int|float|null $value, int $decimals = 0, string $empty = '—'): string
    {
        if ($value === null || $value === '' || !is_numeric($value)) {
            return $empty;
        }
        $formatter = new NumberFormatter($this->numberLocale(), NumberFormatter::DECIMAL);
        $formatter->setAttribute(NumberFormatter::MIN_FRACTION_DIGITS, $decimals);
        $formatter->setAttribute(NumberFormatter::MAX_FRACTION_DIGITS, $decimals);
        $formatter->setAttribute(NumberFormatter::ROUNDING_MODE, NumberFormatter::ROUND_HALFUP);
        $text = $formatter->format($this->unsignedZero((float)$value, $formatter));

        return $text === false ? $empty : $text;
    }

    /**
     * An amount of money in the language of the page: `1 842,30 €` in
     * Finnish, `€1,842.30` in English.
     *
     * @param string|float|int|null $value The amount in the currency's units (`'1842.30'`, not cents).
     * @param string $currency ISO 4217 code.
     * @param int|null $decimals Digits after the separator; null takes the currency's own (2 for EUR).
     * @param string $empty What a missing or unreadable value shows.
     * @return string
     */
    public function money(
        string|int|float|null $value,
        string $currency = 'EUR',
        ?int $decimals = null,
        string $empty = '—',
    ): string {
        if ($value === null || $value === '' || !is_numeric($value)) {
            return $empty;
        }
        $currency = strtoupper($currency);
        $formatter = new NumberFormatter($this->numberLocale(), NumberFormatter::CURRENCY);
        // The currency's own digits (0 for JPY), unless the caller set them.
        $formatter->setTextAttribute(NumberFormatter::CURRENCY_CODE, $currency);
        if ($decimals !== null) {
            $formatter->setAttribute(NumberFormatter::MIN_FRACTION_DIGITS, $decimals);
            $formatter->setAttribute(NumberFormatter::MAX_FRACTION_DIGITS, $decimals);
        }
        $formatter->setAttribute(NumberFormatter::ROUNDING_MODE, NumberFormatter::ROUND_HALFUP);
        $text = $formatter->formatCurrency($this->unsignedZero((float)$value, $formatter), $currency);

        return $text === false ? $empty : $text;
    }

    /**
     * A share as a percentage in the language of the page: `75 %` in
     * Finnish, `75%` in English, for a fraction (`0.75`).
     *
     * @param string|float|int|null $fraction The share as a fraction: `0.75` for 75 %.
     * @param int $decimals Digits after the decimal separator.
     * @param string $empty What a missing or unreadable value shows.
     * @return string
     */
    public function percent(string|int|float|null $fraction, int $decimals = 0, string $empty = '—'): string
    {
        if ($fraction === null || $fraction === '' || !is_numeric($fraction)) {
            return $empty;
        }
        $formatter = new NumberFormatter($this->numberLocale(), NumberFormatter::PERCENT);
        $formatter->setAttribute(NumberFormatter::MIN_FRACTION_DIGITS, $decimals);
        $formatter->setAttribute(NumberFormatter::MAX_FRACTION_DIGITS, $decimals);
        $formatter->setAttribute(NumberFormatter::ROUNDING_MODE, NumberFormatter::ROUND_HALFUP);
        $text = $formatter->format($this->unsignedZero((float)$fraction, $formatter, 100));

        return $text === false ? $empty : $text;
    }

    /**
     * A day or moment as the date picker's value: wall-clock time in the
     * display time zone, `Y-m-d H:i` (`datetime`), `Y-m-d` (`date`), `H:i`
     * (`time`) or `Y-m` (`month`); '' for a missing or unreadable value. A
     * calendar day (a `Date`, a `YYYY-MM-DD` string) is not moved between
     * zones. The posted value is read back in the same zone:
     * `new DateTime($posted, $this->Ui->displayTimezone())`.
     *
     * @param \DateTimeInterface|\Cake\Chronos\ChronosDate|string|int|null $value The day or moment.
     * @param string $mode `datetime`, `date`, `time` or `month`.
     * @return string
     */
    public function pickerValue(DateTimeInterface|ChronosDate|string|int|null $value, string $mode = 'datetime'): string
    {
        $formats = ['datetime' => 'Y-m-d H:i', 'date' => 'Y-m-d', 'time' => 'H:i', 'month' => 'Y-m'];
        if (!isset($formats[$mode])) {
            throw new InvalidArgumentException(sprintf('Unknown picker mode "%s".', $mode));
        }
        if ($value instanceof ChronosDate) {
            return $mode === 'time' ? '' : $value->format($mode === 'month' ? 'Y-m' : 'Y-m-d');
        }
        if (is_string($value) && preg_match(self::DATE_ONLY, $value) === 1) {
            return $mode === 'time' ? '' : ($mode === 'month' ? substr($value, 0, 7) : $value);
        }
        $moment = $this->moment($value);

        return $moment === null ? '' : $moment->format($formats[$mode]);
    }

    /**
     * The display time zone: the `displayTimezone` setting.
     *
     * @return string
     */
    public function displayTimezone(): string
    {
        $zone = UiPlugin::setting('displayTimezone');

        return is_string($zone) && $zone !== '' ? $zone : 'Europe/Helsinki';
    }

    /**
     * A value as a moment in the display time zone; null when there is none.
     *
     * @param \DateTimeInterface|string|int|null $value The moment.
     * @return \Cake\I18n\DateTime|null
     */
    private function moment(DateTimeInterface|string|int|null $value): ?DateTime
    {
        if ($value === null || $value === '') {
            return null;
        }
        try {
            $moment = is_int($value) ? DateTime::createFromTimestamp($value) : DateTime::parse($value);
        } catch (Throwable) {
            return null;
        }

        return $moment->setTimezone($this->displayTimezone());
    }

    /**
     * The value, or 0 when it shows as zero: a tiny negative such as -0.001
     * rounds to zero and would show as `-0,00`.
     *
     * @param float $value The value as formatted.
     * @param \NumberFormatter $formatter Its digits after the separator decide.
     * @param int $scale What the formatter multiplies the value by (100 for a percentage).
     * @return float
     */
    private function unsignedZero(float $value, NumberFormatter $formatter, int $scale = 1): float
    {
        $digits = max(0, (int)$formatter->getAttribute(NumberFormatter::MAX_FRACTION_DIGITS));

        return round(abs($value) * $scale, $digits) === 0.0 ? 0.0 : $value;
    }

    /**
     * The ICU locale numbers are written in: the `numberLocale` setting, else
     * the `locale` setting, else the application's locale. ICU's
     * `en_US_POSIX`, a server's default, groups no thousands, so it counts as
     * `en_US`.
     *
     * @return string
     */
    private function numberLocale(): string
    {
        $locale = I18n::getLocale();
        foreach (['numberLocale', 'locale'] as $setting) {
            $fixed = UiPlugin::setting($setting);
            if (is_string($fixed) && $fixed !== '') {
                $locale = $fixed;
                break;
            }
        }

        return (string)preg_replace('/_POSIX$/i', '', $locale);
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
