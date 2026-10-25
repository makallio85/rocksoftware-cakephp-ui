# rocksoftware-cakephp-ui

The shared screen frame for CakePHP 5 applications: the page layout, the top bar, the slide-out
main menu, the user menu, the sign-in and error layouts, flash messages, styles and icons. Each
application supplies only its name, icon, language and menu, so every application shows the same
frame and none keeps its own copy.

| | Name |
|---|---|
| Composer package | `makallio85/rocksoftware-cakephp-ui` |
| CakePHP plugin, namespace | `RockSoftware/Ui`, `RockSoftware\Ui\` |
| Settings | `Ui.*` |
| Translation domain | `rock_software_ui` (English msgids, Finnish `.po`) |
| Assets | `/rock_software/ui/…` (the plugin's `webroot/`) |

## What the frame is

- **`default` layout** (signed in): a fixed white top bar with the hamburger, the brand and the
  user menu; an off-canvas drawer with the main menu (hover flyouts on desktop, an accordion on
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

## Installing in an application

1. **Composer.** Run `composer require makallio85/rocksoftware-cakephp-ui:^1.0`. Until the package
   is on Packagist, add the repository as a `vcs` entry under `repositories`.
2. **Plugin.** Add `$this->addPlugin(\RockSoftware\Ui\UiPlugin::class);` to
   `Application::bootstrap()`.
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
5. **Settings.** In `config/app.php`:

   ```php
   'Ui' => [
       'appName' => 'Example App',
       'brandIcon' => 'bi bi-rocket',
       'organizationName' => 'Example Ltd',
   ],
   ```

6. **Main menu.** Implement `RockSoftware\Ui\View\MainMenuInterface` and set it as the `mainMenu`
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

### Settings

Every setting has a default in `UiPlugin::DEFAULTS`.

| Setting | Default | |
|---|---|---|
| `appName` | `'Application'` | The name in the top bar, on the sign-in card and in the page title |
| `brandIcon` | `'bi bi-grid-1x2'` | The Bootstrap Icons class of the brand mark |
| `organizationName` | `null` | The line under the name on the sign-in card; null for none |
| `htmlLang` | `null` | The `<html lang>`; null takes it from the locale |
| `bodyClass` | `null` | Classes added to `<body>` beside the frame's `kk-app` / `kk-auth` |
| `titleSuffix` | `null` | What follows the page title; null for ` · <appName>` |
| `homeUrl` | `'/'` | Where the brand and the error page's back link go |
| `storageKeyPrefix` | `'ui'` | Prefix of the browser storage keys that keep the menu's pin and collapsed groups |
| `components` | all `false` | `['breadcrumb' => …, 'modal' => …, 'listTable' => …]` |
| `elements` | `[]` | The application's own elements, by layout and place (below) |
| `locale` | `null` | The language of the frame's texts when it differs from the application's |

In debug mode, the `default` and `auth` layouts prefix the title with `[DEV] `.

### What the application adds to a page

- **Blocks**: `title`, `meta`, `css` and `script` as usual, and `banner` for a notice under the
  top bar on one page.
- **Elements by layout and place**, for what every page of a layout needs, such as a Vite entry
  or data the application's scripts read:

  ```php
  'elements' => [
      'default' => ['head' => ['vite_css'], 'banner' => ['maintenance_notice'], 'bodyEnd' => ['vite_js']],
      'auth' => ['head' => ['vite_css'], 'bodyEnd' => ['vite_js']],
  ],
  ```

  `head` renders after the frame's styles and before the `meta` and `css` blocks; `banner` under
  the top bar (`default` only); `bodyEnd` after the frame's scripts and before the `script` block.
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
