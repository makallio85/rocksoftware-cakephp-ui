<?php
/**
 * The development sandbox's front controller (`docker-compose.yml`): the
 * test application, served with the frame, a fictional menu and user menu,
 * so the frame can be looked at in a browser.
 */
declare(strict_types=1);

use Cake\Core\Configure;
use Cake\Http\Server;
use Cake\Routing\Router;
use RockSoftware\Ui\UiPlugin;
use TestApp\Application;

// Let the built-in server send the plugin's files and anything else on disk.
if (PHP_SAPI === 'cli-server' && is_file(__DIR__ . parse_url((string)$_SERVER['REQUEST_URI'], PHP_URL_PATH))) {
    return false;
}

require dirname(__DIR__, 2) . '/bootstrap.php';

Configure::write('Ui', [
    'appName' => 'Demo App',
    'brandIcon' => 'bi bi-rocket',
    'organizationName' => 'Example Ltd',
    'storageKeyPrefix' => 'demo',
]);
UiPlugin::applyDefaults();
Configure::write('TestApp', [
    'badgeCounts' => ['pendingReports' => 2],
    'userMenuItems' => [
        ['type' => 'header', 'label' => 'Alex Example', 'detail' => 'alex@example.test'],
        ['type' => 'app', 'label' => 'Demo App', 'url' => '/', 'current' => true],
        ['type' => 'app', 'label' => 'Other app', 'url' => 'https://other.example.test/', 'current' => false],
        ['type' => 'link', 'label' => 'Your account', 'url' => 'https://id.example.test/account'],
        ['type' => 'sign_out', 'label' => 'Sign out', 'url' => '/'],
    ],
]);

Router::reload();
$server = new Server(new Application(CONFIG));
$server->emit($server->run());
