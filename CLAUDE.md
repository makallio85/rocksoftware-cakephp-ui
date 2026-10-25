# Contributor guide — rocksoftware-cakephp-ui

A **CakePHP 5 plugin** (Composer package `makallio85/rocksoftware-cakephp-ui`; CakePHP plugin
`RockSoftware/Ui`) holding the shared screen frame applications use as their view theme. See
`README.md` for what it does.

## Quick start

```bash
composer install
vendor/bin/phpunit
composer cs-check
composer stan
```

`docker compose up -d` gives the same in a container and serves the test application on
http://127.0.0.1:8085.

## Rules

1. **Pull requests target `dev`**, never `master`.
2. **The frame is shared; a change changes every application.** Markup, classes and behaviour stay
   as they are unless the change is meant for all of them. Anything one application needs is a
   setting with a default that keeps today's frame (`UiPlugin::DEFAULTS`), an element hook or a
   block, never a fork of a template.
3. **Applications supply, the plugin renders.** The application's name, icon, language, menu
   (`View\MainMenuInterface`) and user menu come in; nothing about any application is written
   into the plugin.
4. **Texts are translatable.** Every user-facing string goes through `__d('rock_software_ui', …)`
   and has a Finnish entry in `resources/locales/fi/rock_software_ui.po` in the same change
   (`TranslationsTest` fails otherwise). A text in a component's script is added to
   `UiHelper::componentTexts()` and to the script's English defaults.
5. **Icons are Bootstrap Icons only.** Scripts are files in `webroot/js/`, never inline.
6. **Public repository.** No names of real applications, organisations, people, hostnames, email
   addresses or phone numbers in code, tests, docs or history. Test data is fictional
   (`*.example.test`).
7. Do not commit `vendor/`, `composer.lock`, `tmp/` or `.phpunit.cache/`.
