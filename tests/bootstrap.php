<?php
declare(strict_types=1);

use Cake\Cache\Cache;
use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\Datasource\ConnectionManager;
use Cake\Utility\Security;
use Migrations\TestSuite\Migrator;
use RockSoftware\Settings\Settings;
use RockSoftware\Settings\SettingsPlugin;
use RockSoftware\Ui\UiPlugin;

require dirname(__DIR__) . '/vendor/autoload.php';

if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}
define('ROOT', dirname(__DIR__) . DS . 'tests' . DS . 'test_app');
define('APP_DIR', 'src');
define('APP', ROOT . DS . APP_DIR . DS);
define('WWW_ROOT', ROOT . DS . 'webroot' . DS);
define('TMP', sys_get_temp_dir() . DS . 'rocksoftware_ui_tests' . DS);
define('LOGS', TMP . 'logs' . DS);
define('CACHE', TMP . 'cache' . DS);
define('CONFIG', ROOT . DS . 'config' . DS);
define('CORE_PATH', dirname(__DIR__) . DS . 'vendor' . DS . 'cakephp' . DS . 'cakephp' . DS);
define('CAKE', CORE_PATH . 'src' . DS);

// The global helpers (__d() and friends) an application's bootstrap loads.
require CAKE . 'functions.php';

foreach ([LOGS, CACHE] as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
}

Configure::write('debug', true);
Configure::write('App', [
    'namespace' => 'TestApp',
    'encoding' => 'UTF-8',
    'defaultLocale' => 'en_US',
    'base' => false,
    'baseUrl' => false,
    'dir' => APP_DIR,
    'webroot' => 'webroot',
    'cssBaseUrl' => 'css/',
    'jsBaseUrl' => 'js/',
    'imageBaseUrl' => 'img/',
    'wwwRoot' => WWW_ROOT,
    'fullBaseUrl' => 'https://app.example.test',
    'paths' => [
        'plugins' => [ROOT . DS . 'plugins' . DS],
        'templates' => [ROOT . DS . 'templates' . DS],
        'locales' => [ROOT . DS . 'resources' . DS . 'locales' . DS],
    ],
]);
Configure::write('Error', [
    'errorLevel' => E_ALL,
    'skipLog' => [],
    'log' => false,
    'trace' => false,
]);
Security::setSalt('rocksoftware-ui-test-salt-0123456789abcdef0123456789');

mb_internal_encoding('UTF-8');

Cache::setConfig([
    '_cake_translations_' => ['className' => 'Null'],
    '_cake_model_' => ['className' => 'Null'],
    '_cake_routes_' => ['className' => 'Null'],
    'default' => ['className' => 'Array'],
]);

// The settings plugin holds the application's name. A file rather than
// :memory:, because the migration runner opens its own connection.
ConnectionManager::setConfig('test', ['url' => 'sqlite:///' . TMP . 'test.sqlite']);
ConnectionManager::alias('test', 'default');
Plugin::getCollection()->add(new SettingsPlugin());
SettingsPlugin::applyDefaults();
SettingsPlugin::loadTranslations();
SettingsPlugin::declareApplicationSettings();
(new Migrator())->run(['connection' => 'test', 'plugin' => SettingsPlugin::NAME]);
Settings::set(SettingsPlugin::APPLICATION_NAME, 'Demo App');

Plugin::getCollection()->add(new UiPlugin(['path' => dirname(__DIR__) . DS]));
UiPlugin::applyDefaults();
UiPlugin::loadTranslations();
