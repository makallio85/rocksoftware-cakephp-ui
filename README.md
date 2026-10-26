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
    filters), as `window.KkListTable`.

  `modal` and `listTable` are Vue 3 components; the application loads Vue itself as `window.Vue`
  and registers them (`app.component('kk-modal', window.KkModal)`).
  - `confirm`: the confirmation dialog of buttons and links with `data-confirm`, and
    `window.KkConfirm.ask()`; loaded by itself on a page that renders an asking
    `Ui->postButton()`;
  - `toast`: `window.appBanner`, short messages in a corner.

  `confirm` and `toast` are plain scripts: no Vue, no build step.
- **Shared in-page pieces** (below): badges, the page header, the kebab menu, the confirmation,
  pagination, toasts, the empty state, form help texts, scrolling tab strips and the date and
  number formats.

## Installing in an application

1. **Composer.** Run `composer require makallio85/rocksoftware-cakephp-ui:^2.0`. It brings
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
| `components` | all `false` | `['breadcrumb' => …, 'modal' => …, 'listTable' => …, 'confirm' => …, 'toast' => …]` |
| `elements` | `[]` | The application's own elements, by layout and place (below) |
| `locale` | `null` | The language of the frame's texts when it differs from the application's |
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

The row's and the page's actions, in one `⋮` dropdown aligned right. Nothing renders when no
item is left.

```php
<?= $this->element('ui/kebab', ['items' => [
    ['type' => 'header', 'title' => 'View'],
    ['type' => 'text', 'html' => $switchMarkup],               // e.g. a form-switch
    ['title' => 'Edit', 'url' => ['action' => 'edit', $id], 'icon' => 'bi bi-pencil'],
    ['title' => 'Resend', 'url' => ['action' => 'resend', $id], 'isDisabled' => true],
    ['type' => 'divider'],
    ['type' => 'post', 'title' => 'Delete', 'url' => ['action' => 'delete', $id],
     'confirm' => 'Delete this message?', 'isDestructive' => true],
], 'label' => 'Actions', 'size' => 'sm']) ?>
```

`size` is `sm` (the default, for a row) or `md`.

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
```

A calendar day (`Cake\I18n\Date`, a `YYYY-MM-DD` string) shows as it is; a moment
(`DateTimeInterface`, an ISO string, a Unix timestamp) shows in the `displayTimezone` setting
(default `Europe/Helsinki`). A missing or unreadable value shows `—`, or the last argument.
Numbers follow the `locale` setting or the application's locale.

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
in a browser.
