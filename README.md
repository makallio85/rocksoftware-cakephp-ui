# rocksoftware-cakephp-ui

The shared screen frame for CakePHP 5 applications: the page layout, the top bar, the slide-out
main menu, the user menu, the sign-in and error layouts, flash messages, styles and icons. Each
application supplies only its icon, language and menu, and its name comes from the shared settings
plugin, so every application shows the same frame and none keeps its own copy.

| | Name |
|---|---|
| Composer package | `makallio85/rocksoftware-cakephp-ui` |
| CakePHP plugin, namespace | `RockSoftware/Ui`, `RockSoftware\Ui\` |
| Settings | `Ui.*`; the name from `makallio85/rocksoftware-cakephp-settings` |
| Translation domain | `rock_software_ui` (English msgids, Finnish `.po`) |
| Assets | `/rock_software/ui/…` (the plugin's `webroot/`) |

## What the frame is

- **`default` layout** (signed in): a fixed white top bar with the hamburger, the brand, the
  application's own controls and the user menu; an off-canvas drawer with the main menu (hover flyouts on desktop, an accordion on
  phones, a pin that docks it open); an optional banner under the top bar; the page content.
- **`auth` layout**: a centred card with the brand mark and the application's name, for sign-in
  pages.
- **`error` layout**: the same card with the error and a link back to the start page.
- **Error pages** (`Error/error400`, `Error/error500`): a short title and text by status, in the
  page's language, inside the `error` layout (see [Error pages](#error-pages)).
- **`ajax` layout**: the content alone, for fragments a script swaps into the page.
- **Elements**: `topbar`, `navigation`, `breadcrumb` and `flash/*` (`default`, `success`,
  `error`, `warning`, `info`, as Bootstrap alerts).
- **Assets**: Bootstrap 5.3 and Bootstrap Icons, `app-chrome.css` (indigo accent, tile grid
  `.kk-tile-grid`, narrow page `.kk-page-narrow`) and `app-chrome.js` (the drawer's pin, the phone
  accordion and the flyout position).
- **Optional components**, each switched on by the application:
  - `breadcrumb`: the trail of pages in the top bar, in place of the brand;
  - `modal`: `<kk-modal>`, the one modal, as `window.KkModal`;
  - `listTable`: `<kk-list-table>`, the one list table (table on desktop, cards on phones,
    filters, selection, a pager), as `window.KkListTable`;
  - `dateTimePicker`: `<kk-date-time-picker>`, the one date and time picker, as
    `window.KkDateTimePicker`;
  - `fileDrop`: `<kk-file-drop>`, the one file input, as `window.KkFileDrop`;
  - `vue`: Vue 3's runtime-only build as `window.Vue`, for an application without Vue of its
    own;
  - `format`: the date and number formats in a script, as `window.KkFormat`.

  `modal`, `listTable`, `dateTimePicker` and `fileDrop` are Vue 3 components, precompiled into
  one plain script that needs no template compiler (see [Vue components](#vue-components)).
  - `confirm`: the confirmation dialog of buttons and links with `data-confirm`, and
    `window.KkConfirm.ask()`; loaded by itself on a page that renders an asking
    `Ui->postButton()`;
  - `toast`: `window.appBanner`, short messages in a corner;
  - `expandToggle`: the show-more row of buttons with `data-kk-expand-toggle`, as
    `window.KkExpandToggle`; loaded by itself on a page that renders `ui/expand_toggle`.

  `confirm`, `toast` and `expandToggle` are plain scripts: no Vue, no build step.
- **Shared in-page pieces** (below): badges, the page header, the kebab menu, the confirmation,
  pagination, toasts, the empty state, the notice, the expand toggle, form help texts, scrolling tab strips and the date and
  number formats.

## Installing in an application

1. **Composer.** Run `composer require makallio85/rocksoftware-cakephp-ui:^2.3`. It brings
   `makallio85/rocksoftware-cakephp-settings`, which holds the application's name.
2. **Plugins.** Add both to `Application::bootstrap()`:

   ```php
   $this->addPlugin(\RockSoftware\Settings\SettingsPlugin::class);
   $this->addPlugin(\RockSoftware\Ui\UiPlugin::class);
   ```

   Install the settings plugin as its README says: its migrations, its `access` and its menu link.
3. **Theme.** Use the plugin as the view theme, in `src/View/AppView.php`:

   ```php
   public function initialize(): void
   {
       $this->setTheme(\RockSoftware\Ui\UiPlugin::NAME);
   }
   ```

   The theme's layouts, elements and assets come before the application's own, so
   `setLayout('auth')`, `setLayout('error')` and `$this->Flash->render()` find the frame's. Delete
   the application's own copies.
4. **Assets.** The files are served from `/rock_software/ui/…` by CakePHP's `AssetMiddleware`
   (or symlinked with `bin/cake plugin assets symlink`).
5. **Name.** The top bar, the sign-in card and every page title show the setting
   `application.name`; the sign-in card shows `application.organization_name` under it when it is
   not empty. Both are on the settings page (Settings › Application). Seed them in a migration of
   the application's own, after the settings plugin's, so the first deploy shows the right name:

   ```php
   use RockSoftware\Settings\Settings;
   use RockSoftware\Settings\SettingsPlugin;

   public function up(): void
   {
       Settings::set(SettingsPlugin::APPLICATION_NAME, 'Example App');
       Settings::set(SettingsPlugin::APPLICATION_ORGANIZATION_NAME, 'Example Ltd');
   }
   ```

   In the application's tests, seed the name the same way, or with the settings plugin's fixtures
   and `Settings::set()`.
6. **Settings.** In `config/app.php`:

   ```php
   'Ui' => [
       'brandIcon' => 'bi bi-rocket',
   ],
   ```

7. **Main menu.** Implement `RockSoftware\Ui\View\MainMenuInterface` and set it as the `mainMenu`
   view variable, usually in `AppController::beforeRender()`:

   ```php
   $this->set('mainMenu', new MainMenu($permissions, $badgeCounts));
   ```

   `groups()` returns the groups the person sees, in order:

   ```php
   [
       [
           'title' => 'Work',
           'icon' => 'bi bi-briefcase',
           'items' => [
               [
                   'title' => 'Reports',
                   'url' => ['plugin' => null, 'controller' => 'Reports', 'action' => 'index'],
                   'badge' => 'pendingReports',          // optional
                   'badgeLabel' => 'waiting for review', // optional, read by screen readers
               ],
           ],
       ],
   ]
   ```

   An item is active when the request's controller and plugin are its URL's. `badgeCounts()`
   returns the count of each badge key; a badge shows when its count is above zero.

   A group with a `url` and no `items` is one direct link: it has the group's look (icon and
   title), no caret and no flyout or accordion, and it is active the way an item is. Use it for a
   menu entry that would otherwise be a group of one:

   ```php
   [
       'title' => 'Dashboard',
       'icon' => 'bi bi-house',
       'url' => ['plugin' => null, 'controller' => 'Dashboard', 'action' => 'index'],
   ]
   ```

### Settings

Every setting has a default in `UiPlugin::DEFAULTS`.

| Setting | Default | |
|---|---|---|
| `brandIcon` | `'bi bi-grid-1x2'` | The Bootstrap Icons class of the brand mark |
| `htmlLang` | `null` | The `<html lang>`; null takes it from the locale |
| `bodyClass` | `null` | Classes added to `<body>` beside the frame's `kk-app` / `kk-auth` |
| `titleSuffix` | `null` | What follows the page title; null for ` · ` and `application.name` |
| `homeUrl` | `'/'` | Where the brand and the error page's back link go |
| `storageKeyPrefix` | `'ui'` | Prefix of the browser storage keys that keep the menu's pin and collapsed groups |
| `components` | all `false` | `['breadcrumb' => …, 'modal' => …, 'listTable' => …, 'dateTimePicker' => …, 'fileDrop' => …, 'vue' => …, 'format' => …, 'confirm' => …, 'toast' => …]` |
| `elements` | `[]` | The application's own elements, by layout and place (below) |
| `locale` | `null` | The language of the frame's texts when it differs from the application's |
| `numberLocale` | `null` | The locale of numbers, money and percentages when it differs from the texts' (`fi_FI`); null follows `locale` |
| `displayTimezone` | `'Europe/Helsinki'` | The time zone `Ui->date()` and `Ui->dateTime()` show moments in |

In debug mode, the `default` and `auth` layouts prefix the title with `[DEV] `.

### What the application adds to a page

- **Blocks**: `title`, `meta`, `css` and `script` as usual, and `banner` for a notice under the
  top bar on one page.
- **Elements by layout and place**, for what every page of a layout needs, such as a Vite entry
  or data the application's scripts read:

  ```php
  'elements' => [
      'default' => [
          'head' => ['vite_css'],
          'topbarEnd' => ['workspace_switcher'],
          'banner' => ['maintenance_notice'],
          'bodyEnd' => ['vite_js'],
      ],
      'auth' => ['head' => ['vite_css'], 'bodyEnd' => ['vite_js']],
  ],
  ```

  `head` renders after the frame's styles and before the `meta` and `css` blocks; `topbarEnd` in
  the top bar, right-aligned just before the user menu (`default` only), for a small control
  such as a switcher; `banner` under the top bar (`default` only); `bodyEnd` after the frame's
  scripts and before the `script` block. The top bar keeps one line: on a narrow screen the
  `topbarEnd` content shrinks and is clipped rather than wrapped, so keep it compact there (hide
  labels below 576px, let a `form-select` shrink).
- **User menu**: the signed-in person, the other applications, the account link and sign-out come
  from `makallio85/rocksoftware-cakephp-users` (`UserMenu` helper) when it is loaded. An
  application without it sets the `userMenuItems` view variable in the same shape: entries of type
  `header` (`label`, `detail`), `app` (`label`, `url`, `current`), `link` (`label`, `url`) and
  `sign_out` (`label`, `url`, posted by a plain form, no script). No entries, no user menu.
- **Content-Security-Policy**: the frame renders no inline scripts, inline event handlers or
  `javascript:` URLs, so it works under `script-src 'self'`.
- **Breadcrumb**: with the component on, the `breadcrumb` view variable lists the pages from the
  top down, each `['title' => …, 'url' => array|string|null]`; the last is the page itself. On
  phones only the page above shows, as a back link.

### Error pages

The plugin has `templates/Error/error400.php` and `error500.php`. Outside debug mode they render in
the `error` layout with a short title and a line of text by status, in the page's language
(English and Finnish, `UiHelper::errorText()`):

| Status | Title |
|---|---|
| 404 | Page not found (Sivua ei löydy) |
| 403 | You do not have access to this page (Sinulla ei ole oikeutta tähän sivuun) |
| other 4xx | The request could not be handled (Pyyntöä ei voitu käsitellä) |
| 5xx | Something went wrong (Jotain meni pieleen) |

The exception's message is not shown (it stays in the log). In debug mode they keep CakePHP's
developer page (`dev_error` layout, the file and the trace), as the application template's do.

An application gets them by using the plugin as its theme (step 3 above): the theme's templates
come before the application's, so its own `templates/Error/error400.php` and `error500.php` are
no longer used. Delete them. The error controller must render with the themed `AppView`, which
CakePHP's own `ErrorController` and an application's `ErrorController extends AppController` do.

### Upgrading from 1.x

Version 2.0 takes the application's name and the line under it from the settings plugin. The
settings `Ui.appName` and `Ui.organizationName` are gone; a value still set there is ignored.

1. Require `makallio85/rocksoftware-cakephp-ui:^2.0`, add `SettingsPlugin` to
   `Application::bootstrap()` and install it as its README says (migrations, `access`, menu link).
2. Add a migration that seeds `application.name` and `application.organization_name` with
   `Settings::set()`, as above, with the values `Ui.appName` and `Ui.organizationName` held.
3. Remove `appName` and `organizationName` from `Ui` in `config/app.php`.

## Shared in-page pieces

Everything here works without Vue or a build step and under a Content-Security-Policy of
`script-src 'self'` (no inline script, no inline handlers). Their styles (`kk-components.css`)
load on every layout. A template that uses the `Ui` helper before the layout runs loads it in
`AppView::initialize()`:

```php
$this->addHelper('RockSoftware/Ui.Ui');
```

The test application's components page (`/components` in the sandbox) shows all of them.

### Badges

```php
<?= $this->Ui->badge('Delivered', 'ok') ?>
<?= $this->Ui->badge('Queued', 'pending', ['title' => 'Waiting for the gateway']) ?>
```

Tones: `ok`, `pending`, `danger`, `muted` (the default), `info`, `tag`
(`UiHelper::BADGE_TONES`); any other throws. The application maps its own states to a tone. The
markup is `<span class="kk-badge kk-badge-ok">`; the colours are set explicitly, as Bootstrap's
`text-bg-*` does not render in this theme. Options: `title`, `class`, `escape`.

### Page header

```php
<?= $this->element('ui/page_header', [
    'title' => 'Messages',
    'subtitle' => 'Everything sent in the last 90 days.',      // optional
    'badges' => $this->Ui->badge('Live', 'ok'),                // optional markup beside the title
    'actions' => [                                             // optional; or a string of markup
        ['title' => 'New message', 'url' => ['action' => 'add'], 'icon' => 'bi bi-plus-lg', 'isPrimary' => true],
        ['title' => 'Refresh', 'type' => 'button', 'attributes' => ['id' => 'refresh']],
        ['title' => 'Purge', 'type' => 'post', 'url' => ['action' => 'purge'], 'confirm' => 'Purge every message?'],
    ],
    'menu' => [['title' => 'Export', 'url' => ['action' => 'export']]],   // optional kebab
    'menuLabel' => 'More actions',                                        // optional
]) ?>
```

An action is a link (`url`), a `post` (an asking `Ui->postButton()`, with `confirm`,
`confirmTitle`, `confirmButton`, `confirmTone`, `data`, `method`) or a `button` for a script.
On phones an action with an icon shows the icon alone (its title stays the accessible name).

### Kebab menu

The row's and the page's actions, in one `⋯` dropdown aligned right. Nothing renders when no
item is left.

```php
<?= $this->element('ui/kebab', ['items' => [
    ['type' => 'header', 'title' => 'View'],
    ['type' => 'text', 'html' => $switchMarkup],               // e.g. a form-switch
    ['title' => 'Edit', 'url' => ['action' => 'edit', $id], 'icon' => 'bi bi-pencil'],
    ['title' => 'Resend', 'url' => ['action' => 'resend', $id], 'isDisabled' => true],
    ['type' => 'button', 'title' => 'Archive…', 'icon' => 'bi bi-archive',   // a script acts on it
     'attributes' => ['data-kk-modal-open' => 'archive-' . $id]],
    ['type' => 'divider'],
    ['type' => 'post', 'title' => 'Delete', 'url' => ['action' => 'delete', $id],
     'confirm' => 'Delete this message?', 'isDestructive' => true],
], 'label' => 'Actions', 'size' => 'sm']) ?>
```

`size` is `sm` (the default, for a row) or `md` (a little larger, for a page header).

The button is a ghost button with horizontal dots: a grey `⋯` (`bi-three-dots`) with no border
or background, a light background on hover and while its menu is open, an outline on keyboard
focus and a larger target on touch screens. Any other button whose content is the
`bi-three-dots-vertical` icon (a hand-written Bootstrap dropdown toggle, a Vue component's) gets
the same look from `kk-components.css`, its icon turned a quarter so it reads `⋯` too, so an
application styles none of its own.

A `button` item is a `<button type="button" class="dropdown-item">` with no address: its
`attributes` (any HTML attributes) say what a script does with it, such as
`data-kk-modal-open` opening a markup modal. It takes `icon`, `isDestructive` and `isDisabled`
like a link. A `text` item is for markup that is no action (a switch), not for a button.

### Confirmation before a destructive action

```php
<?= $this->Ui->postButton('Delete', ['action' => 'delete', $id], [
    'class' => 'btn btn-outline-danger',
    'confirm' => 'Messages already sent stay in the log. This cannot be undone.',
    'confirmTitle' => 'Delete this provider?',   // default: Are you sure?
    'confirmButton' => 'Delete',                 // default: Confirm
    'confirmTone' => 'danger',                   // or primary
]) ?>
```

This replaces `postLink(…, ['confirm' => …])` and `postButton(…, ['confirm' => …])`, whose
prompt is an inline `onclick` that a strict policy refuses. The question goes into
`data-confirm`, `data-confirm-title`, `data-confirm-button` and `data-confirm-tone`, and
`kk-confirm.js` shows the dialog; the page loads it by itself. The form is `d-inline` unless
`form.class` says otherwise. The same attributes work on any submit button, form or link the
application writes itself (with the `confirm` component on). Only the button that submitted asks:
a form with a Save and a Delete button asks for Delete only.

From a script: `window.KkConfirm.ask({message, title, button, tone}).then((isConfirmed) => …)`.

### Pagination

```php
<?= $this->element('ui/pagination') ?>
<?= $this->element('ui/pagination', ['results' => $messages, 'modulus' => 6, 'showsCounter' => false]) ?>
```

"Showing 1–20 of 240" and the page links with Bootstrap 5 markup, separated squares, the current
page in the accent colour; the edges and ellipses drop on phones. Nothing renders for an empty
list, the counter alone for one page. The templates (`UiHelper::PAGINATOR_TEMPLATES`) apply to
the element only; for the table's sort links too, load the paginator with them:

```php
$this->loadHelper('Paginator', ['templates' => \RockSoftware\Ui\View\Helper\UiHelper::PAGINATOR_TEMPLATES]);
```

### Toasts

With the `toast` component on (or `$this->Ui->requireComponent('toast')` in one page's template):

```js
window.appBanner.success('Saved.');
window.appBanner.error({ title: 'Could not send.', text: 'The gateway did not answer.' });
window.appBanner.warning('Check the address.');
window.appBanner.info('Refreshing…');
window.appBanner('success', 'Saved.', 0);   // kind, message, ms until it closes (0 = until closed)
```

Top-right under the top bar on desktop, at the bottom on phones. Errors stay 6 s, the rest 3.5 s.
The text is set as text, never as markup.

### Empty state

```php
<?= $this->element('ui/empty_state', [
    'title' => 'No messages yet',
    'text' => 'Messages appear here once an application sends one.',   // optional
    'icon' => 'bi bi-inbox',                                            // optional
    'action' => $this->Html->link('New message', ['action' => 'add'], ['class' => 'btn btn-primary']),  // optional
]) ?>
```

### Notice

A boxed note inside a page: what the reader should know about what the page shows, such as a
proposal that waits for review or a draft that is out of date. For a message about something
that just happened, use a flash message or a toast.

```php
<?= $this->element('ui/notice', [
    'tone' => 'warning',                                    // optional; default info
    'title' => 'The draft is out of date.',                 // optional, bold above the text
    'text' => 'The provider changed after the draft was made.',   // escaped; or 'html' => markup
    'icon' => 'bi bi-exclamation-triangle',                 // optional
    'action' => $this->Html->link('Compare', ['action' => 'compare', 12],
        ['class' => 'btn btn-sm btn-outline-secondary']),   // optional markup, right of the text
]) ?>
```

Tones (`UiHelper::NOTICE_TONES`): the badge tones `ok`, `pending`, `danger`, `muted`, `info`,
`tag`, and `warning`; any other throws. `warning` is solid amber, for a caution; `pending` is a
dashed amber box, for something not settled yet (undecided, not verified); `tag` is the accent's
indigo; `info` blue. The colours are set explicitly, as badges' are.

A `danger` notice has `role="alert"`, every other `role="note"`; `role` sets another, such as
`status` for a notice a script updates. Options: `tone`, `title`, `text` or `html` (markup the
caller escaped, e.g. with a link), `action`, `icon`, `role`, `class`, `id`. On phones the action
goes under the text and its buttons are 44px tall.

### Expand toggle

The row across the bottom of a compact section that shows and hides the rest of it: a thin line
with a centred label, "Show more" with a down chevron while closed, "Show less" with an up chevron
while open. The whole row is one button, inside the section.

```php
<dl id="provider-7-more" hidden>…</dl>   <!-- hidden while the toggle starts closed -->
<?= $this->element('ui/expand_toggle', [
    'target' => 'provider-7-more',       // the id of the element it shows and hides
    'expanded' => false,                 // optional; true starts it open
    'showLabel' => 'All settings',       // optional; default "Show more"
    'hideLabel' => 'Fewer settings',     // optional; default "Show less"
]) ?>
```

The element loads `kk-expand-toggle.js` on the page. A click sets the button's `aria-expanded`,
its label and chevron and the target's `hidden`, and dispatches a bubbling `kk:expand-toggle`
event on the button with `{ expanded }`, so an application can remember the state:

```js
document.addEventListener('kk:expand-toggle', (event) => {
    localStorage.setItem(event.target.getAttribute('aria-controls'), String(event.detail.expanded));
});
window.KkExpandToggle.setExpanded(button, true);   // open one from a script, without the event
```

On load the script sets each target's `hidden` from its toggle's `aria-expanded`. Markup the
application writes itself (`.kk-expand-toggle` with `__line` and `__label` parts, as the element
renders it) works with the `expandToggle` component on. The line is `#e9ecef` and the label
`#5c636a`, 12px semibold; on hover the label darkens and the line gets stronger; on phones the row
is 44px tall.

### Form help texts and validation

An opt-in form helper, loaded as the application's `Form`:

```php
$this->addHelper('Form', ['className' => 'RockSoftware/Ui.Form']);
```

- `$this->Form->control('sender_name', ['help' => 'Shown to the recipient.'])` renders the text
  under the field as `div.form-text` (escaped) and points `aria-describedby` at it.
- A field with an error gets `is-invalid` and its message `.invalid-feedback`.
- Required fields carry no inline `oninvalid` / `oninput` handlers (core's
  `autoSetCustomValidity`, which a strict policy refuses); pass
  `'autoSetCustomValidity' => true` to have them back.

### Tab strips that scroll

`<ul class="nav nav-tabs nav-tabs-scrollable">`: the tabs stay on one line and scroll sideways
on a narrow screen.

### Dates and numbers

```php
$this->Ui->date($value);                 // 26.10.2026
$this->Ui->dateTime($value);             // 26.10.2026 08.14 (in the display time zone)
$this->Ui->number(1842.3, 2);            // 1 842,30 in Finnish, 1,842.30 in English
$this->Ui->money('1842.30');             // 1 842,30 € in Finnish, €1,842.30 in English
$this->Ui->money(7000, 'USD', 0);
$this->Ui->percent(0.75);                // 75 % in Finnish, 75% in English (a fraction)
$this->Ui->percent('0.0425', 1);         // 4,3 % / 4.3%
$this->Ui->money('-0.001');              // 0,00 € / €0.00: what rounds to zero has no minus
```

A calendar day (`Cake\I18n\Date`, a `YYYY-MM-DD` string) shows as it is; a moment
(`DateTimeInterface`, an ISO string, a Unix timestamp) shows in the `displayTimezone` setting
(default `Europe/Helsinki`). A missing or unreadable value shows `—`, or the last argument.
Numbers, money and percentages follow the `numberLocale` setting, else the `locale` setting, else
the application's locale: an application with English texts and Finnish numbers sets
`'numberLocale' => 'fi_FI'`.
PHP's `intl` writes a locale only when its ICU data is installed: Alpine's `icu-libs` carries
English alone (`icu-data-en`), so an Alpine image that shows Finnish numbers adds
`icu-data-full`; without it they come out in English.

## Vue components

`<kk-list-table>`, `<kk-modal>`, `<kk-date-time-picker>`, `<kk-file-drop>` and `<kk-pager>` are
Vue 3 single-file components in `resources/js/components/`, built into one plain script,
`webroot/js/components/kk-vue-components.js`, with render functions compiled ahead of time. No
template is compiled in the browser and nothing uses `eval`, so they run under a
Content-Security-Policy of `script-src 'self'`. The calendar (`@vuepic/vue-datepicker`) is
bundled into that script; nothing loads from a CDN. Their styles are plain files in
`webroot/css/` the layout loads.

The script reads Vue from `window.Vue` the first time a component is used, so one copy of Vue
serves the application and the components, and the application's Vue may load after the
plugin's scripts. It defines `window.KkUi` (a Vue plugin that registers every component),
`window.KkListTable`, `window.KkModal`, `window.KkDateTimePicker`, `window.KkFileDrop` and
`window.KkPager`.

### Mounting them in an application

**Without Vue of its own** (server-rendered pages): switch on `vue` beside the components, and
write the components with the plugin's elements. Each element writes plain markup with
`data-kk-*` attributes, which the script turns into the component once the page is parsed:

```php
'components' => ['vue' => true, 'listTable' => true, 'modal' => true, 'dateTimePicker' => true, 'fileDrop' => true],
```

An element loads its own component on the page (`ui/list_table` → `listTable` and so on), so
only `vue` has to be on. Markup a script adds later is mounted with
`window.KkUi.mount(element)`.

The markup mounts once the page is parsed and `window.Vue` is there: as parsing ends when Vue
is already loaded (the plugin's `vue`, or Vue as a plain script before the plugin's), else after
`DOMContentLoaded`, which comes after deferred and module scripts, so a Vite bundle that sets
`window.Vue` (below) gets its elements mounted too. Should Vue come later still, the script
retries for a few seconds after the window's `load`, then warns in the console; such a page
calls `window.KkUi.mount(document)` itself once it has set `window.Vue`.

**With Vue loaded as a plain script** (`window.Vue`, the global build): leave `vue` off, switch
on the components, and register them in the application's own apps:

```js
const app = Vue.createApp({ … });
app.use(window.KkUi);              // or app.component('kk-list-table', window.KkListTable)
app.mount('#suppliers');
```

A template written in the page's HTML (an in-DOM template) is compiled in the browser, which
needs Vue's full build and `'unsafe-eval'`. Under a strict policy, use the elements above or
render functions (`Vue.h(window.KkListTable, {…})`, as the components page's `demo.js` does).

**With Vite** (Vue in the application's bundle): leave `vue` off and switch on the components,
so the layout loads their styles and texts. Then either

- hand the bundle's Vue to the plugin's script before the first component is used, and register
  them:

  ```js
  import * as Vue from 'vue';
  window.Vue = Vue;
  createApp(Root).use(window.KkUi).mount('#app');
  ```

- or import the sources and let Vite build them into the application's bundle (the
  application then installs `@vuepic/vue-datepicker` at the version in the plugin's
  `package.json`):

  ```js
  import { KkUi } from '../../vendor/makallio85/rocksoftware-cakephp-ui/resources/js/index.js';
  createApp(Root).use(KkUi).mount('#app');
  ```

Every component reads its texts from the layout's `kk-ui-texts` block (English defaults in
`resources/js/texts.js`), so it speaks the page's language either way.

### List table

A sortable table on desktop, one card per row below Bootstrap `lg`, the filters above it,
optional checkboxes with a bulk bar, and a pager. It never fetches.

From PHP (no Vue of its own), a server-paged list:

```php
<?= $this->element('ui/list_table', [
    'results' => $messages,                         // the paginated rows
    'columns' => [
        ['key' => 'sentAt', 'label' => 'Sent', 'sortField' => 'sent_at'],
        ['key' => 'recipient', 'label' => 'Recipient', 'sortable' => false],
        ['key' => 'status', 'label' => 'Status', 'sortable' => false, 'isHtml' => true],
    ],
    'rows' => array_map(fn($message) => [
        'id' => $message->id,
        'sentAt' => $this->Ui->dateTime($message->sent_at),
        'recipient' => $message->recipient,
        'status' => $this->Ui->badge($message->status_label, $message->tone),   // markup: isHtml
        '_actions' => $this->element('ui/kebab', ['items' => $rowMenu($message)]),
    ], $messages->toArray()),
    'filters' => [
        ['key' => 'q', 'label' => 'Search', 'type' => 'text', 'param' => 'q'],
        ['key' => 'status', 'label' => 'Status', 'type' => 'select', 'param' => 'status',
         'options' => [['value' => 'failed', 'label' => 'Failed']]],
        ['key' => 'sent', 'label' => 'Sent', 'type' => 'date_range', 'paramFrom' => 'from', 'paramTo' => 'to'],
    ],
    'filterValues' => ['q' => $q, 'status' => $status, 'sent' => ['from' => $from, 'to' => $to]],
    'selectable' => true,
    'selectionName' => 'ids',                 // ticked rows post as ids[] …
    'selectionForm' => 'bulk-form',           // … with this form
    'bulk' => '<button type="submit" form="bulk-form" class="btn btn-sm btn-outline-danger">Delete</button>',
]) ?>
```

With `results`, the order is the paginator's: a header click reloads the page with
`?sort=<sortField or key>&direction=asc|desc` (allow the field in the paginator's
`sortableFields`), the `ui/pagination` element shows under the rows, and a filter with `param`
reloads the page with its value (a text filter on Enter or when the field is left). Without
`results`, the rows are all on the page and are sorted and filtered in the browser: a filter
with `matchField` compares that row field, a text filter searches the plain columns (or a row's
`_search`). A row with `url` opens it when clicked (`rowClickable`).

Filters a person sets for themselves are kept in the browser with `rememberFilters`:

```php
'id' => 'handovers',                              // required with rememberFilters
'rememberFilters' => ['repository', 'status'],    // filter keys
```

Each value is saved per list, under `<storageKeyPrefix>:list-filters:<id>:<filter key>` in
`localStorage`, when it changes, and restored over `filterValues` when the page loads. A value
that no longer fits its filter (an option that has gone) is ignored, a filter set back to all is
forgotten, and storage that is blocked or full is ignored. Only filters narrowed in the browser
are remembered (`matchField`, or a text filter without `param`); a server filter (`param`) is in
the address and is not.

In a Vue app:

```html
<kk-list-table :rows="rows" :columns="columns" :filters="filters"
               v-model:filter-values="filterValues" v-model:sort-key="sortKey" v-model:sort-asc="sortAsc"
               selectable v-model:selected="selected" row-clickable @row-click="open"
               is-server-sorted :pagination="{ page, perPage: 20, count }" @update:page="load">
    <template #cell-status="{ row }"><span class="kk-badge kk-badge-ok">{{ row.status }}</span></template>
    <template #actions="{ row }">…</template>
    <template #bulk="{ selected }">…</template>
    <template #pager>…a pager of your own…</template>
</kk-list-table>
```

Props: `rows`, `rowKey`, `columns` (`key`, `label`, `sortable`, `sortValue(row)`, `class`),
`filters` (`select` with `options` and `multiple`, `text`, `date_range`; `match(row, value)`
filters here, none means the server's), `filterValues`, `sortKey`, `sortAsc`, `isServerSorted`,
`pagination` (`page`, `perPage`, `count`, optional `url` with `{page}`), `selectable`,
`selected`, `rowClickable`, `loading`, `error`, `emptyText`, `countLabel(n)`, `rememberFilters`
(filter keys, as above; only filters with `match`) with `listId` (the list's id in the storage
keys; the prefix comes from the layout's `kk-ui-texts`). Restored values are emitted as
`update:filterValues` once the table is mounted. Events: the
`update:*` of each, `row-click`, and `filter-commit` (key, value) when a filter's value is settled.
`<kk-pager>` is the pager alone: `page`, `perPage`, `count`, `modulus`, `url`, `showsCounter`.

### Modal

The one modal. The header holds the title, the move handle (desktop) and the close button, and
nothing else; the body scrolls; the footer holds the buttons. Esc, the close button and the
backdrop close it; on phones it fills the screen.

From PHP: the body and footer are the page's own markup, so a form in it posts as usual.

```php
<?= $this->element('ui/modal', [
    'name' => 'reject-12',
    'title' => 'Reject the request?',
    'body' => $this->Form->create(null, ['url' => ['action' => 'reject', 12], 'id' => 'reject-12-form'])
        . $this->Form->control('reason') . $this->Form->end(),
    'footer' => '<button type="button" class="btn btn-outline-secondary" data-kk-modal-close>Cancel</button>'
        . '<button type="submit" form="reject-12-form" class="btn btn-danger">Reject</button>',
    'size' => 'md',            // sm, md, lg, xl
]) ?>
<button type="button" class="btn btn-outline-danger" data-kk-modal-open="reject-12">Reject</button>
```

From a script: `window.KkUi.openModal('reject-12')`, `window.KkUi.closeModal('reject-12')`.
In a Vue app: `<kk-modal :open="isOpen" title="…" @close="isOpen = false">…<template #footer>…</template></kk-modal>`
(props `open`, `title`, `size`, `closable`; slots default, `title`, `tabs`, `footer`).

The older `<div data-kk-modals hidden>` blocks (`kk-modal-markup.js`) still work, but their
template is compiled in the browser: they need Vue's full build and `'unsafe-eval'`.

### Date and time picker

A field showing `DD.MM.YYYY HH.mm` that opens the calendar with a time list. Its value is
wall-clock time in the display time zone (`displayTimezone`, default Europe/Helsinki):
`Y-m-d H:i` (mode `datetime`), `Y-m-d` (`date`), `H:i` (`time`) or `Y-m` (`month`).

```php
<?= $this->element('ui/date_time_picker', [
    'name' => 'send_after',
    'label' => 'Send after',
    'value' => $message->send_after,      // a moment is shown in Helsinki time
    'help' => 'Helsinki time.',
    'error' => $message->getError('send_after')[0] ?? null,
    'mode' => 'datetime',                 // date, time, month
    'timeStep' => 30, 'min' => '2026-01-01', 'max' => null,
]) ?>
```

The form posts the value as before; read it back in the same zone:
`new DateTime($data['send_after'], $this->Ui->displayTimezone())` (or the zone setting in a
controller). `$this->Ui->pickerValue($moment, $mode)` turns a day or moment into the value. In a
Vue app: `<kk-date-time-picker v-model="form.sendAfter" mode="datetime" size="sm"></kk-date-time-picker>`
(props `mode`, `modelType`, `timeStep`, `size`, `clearable`, `min`, `max`, `inputClass`,
`disabled`, `placeholder`, `inputId`, `ariaLabel`, `describedBy`).

### File drop

A dashed target to click or drop files on, the chosen files listed with their sizes; a file of
the wrong type or too large is refused with a message.

```php
<?= $this->element('ui/file_drop', [
    'name' => 'attachment',
    'label' => 'Attachment',
    'accept' => '.pdf,image/*',
    'maxBytes' => 10 * 1024 * 1024,
    'hint' => 'PDF or an image, at most 10 MB.',
    'isMultiple' => false,
]) ?>
```

The file input stays in the form (create it with `'type' => 'file'`) and holds the files
chosen, so the form posts them as before. In a Vue app:
`<kk-file-drop v-model="files" accept="image/*" :max-bytes="…" hint="…" @change="upload"></kk-file-drop>`
(events `update:modelValue`, `change`, `rejected`).

### Formats in a script

With the `format` component on (no Vue needed), `window.KkFormat` writes what the PHP helper
writes, in the page's number locale and display time zone:

```js
KkFormat.formatDate('2026-10-26T22:30:00Z');      // 27.10.2026
KkFormat.formatDateTime('2026-10-26T06:14:00Z');  // 26.10.2026 08.14
KkFormat.formatNumber(1842.3, 2);                 // 1 842,30 in Finnish, 1,842.30 in English
KkFormat.formatMoney('1842.30');                  // 1 842,30 € in Finnish, €1,842.30 in English
KkFormat.formatPercent(0.75);                     // 75 % in Finnish, 75% in English
KkFormat.formatFileSize(2516582);                 // 2.4 MB
```

A string without an offset is read as UTC; a missing value shows `—` or the last argument.
A Vite app imports the same functions from `resources/js/format.js`.

### Building the components

The built files in `webroot/` are committed with their sources. After changing anything in
`resources/js/` or `resources/css/`:

```bash
npm ci
npm test              # the components' unit tests (Vitest)
npm run build         # writes webroot/js/components/kk-vue-components.js, kk-format.js,
                      # vue.runtime.global.prod.js and webroot/css/kk-date-time-picker.css
```

CI builds again and fails when the output differs from what is committed, and opens the
components page in Chromium behind the test application's strict policy
(`tests/browser/demo-page.mjs`), failing on any console error or policy violation.

## Translations

Every text of the frame is in English (the msgids) and Finnish
(`resources/locales/fi/rock_software_ui.po`). The application's locale picks one; the `locale`
setting overrides it. The optional components' scripts read their texts from a JSON block the
layout writes (`#kk-ui-texts`).

## Developing

```bash
docker compose up -d                    # the test application on http://127.0.0.1:8085
docker compose exec app vendor/bin/phpunit
docker compose exec app composer cs-check
docker compose exec app composer stan
```

The sandbox serves `tests/test_app` with a fictional menu and user so the frame can be looked at
in a browser. The components page is `/components`; `/no-such-page` and `/dashboard/error/403`,
`/400` and `/500` fail on purpose. Two environment variables change the sandbox:
`UI_LOCALE=fi` shows the frame's texts in Finnish, and `DEBUG=false` shows the error pages as
production does instead of CakePHP's developer page.
