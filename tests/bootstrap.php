<?php

use Cake\Datasource\ConnectionManager;
use Cake\Routing\Router;
use Cake\Routing\Route\DashedRoute;

if (!defined('DS')) {
	define('DS', DIRECTORY_SEPARATOR);
}
if (!defined('WINDOWS')) {
	if (DS === '\\' || substr(PHP_OS, 0, 3) === 'WIN') {
		define('WINDOWS', true);
	} else {
		define('WINDOWS', false);
	}
}

define('ROOT', dirname(__DIR__));
define('TMP', ROOT . DS . 'tmp' . DS);
define('LOGS', TMP . 'logs' . DS);
define('CACHE', TMP . 'cache' . DS);
define('APP', sys_get_temp_dir());
define('APP_DIR', 'src');
define('CAKE_CORE_INCLUDE_PATH', ROOT . '/vendor/cakephp/cakephp');
define('CORE_PATH', CAKE_CORE_INCLUDE_PATH . DS);
define('CAKE', CORE_PATH . APP_DIR . DS);
define('TESTS', ROOT . DS . 'tests' . DS);

define('WWW_ROOT', ROOT . DS . 'webroot' . DS);
define('CONFIG', __DIR__ . DS . 'config' . DS);

ini_set('intl.default_locale', 'de-DE');

require_once 'vendor/cakephp/cakephp/src/basics.php';
require_once 'vendor/autoload.php';

Cake\Core\Configure::write('App', [
	'namespace' => 'TestApp',
	'encoding' => 'UTF-8',
	'fullBaseUrl' => '/',
	'paths' => [
		'templates' => [
			TESTS . 'templates' . DS,
		],
	],
]);
Cake\Core\Configure::write('debug', true);

Cake\Core\Configure::write('Config', [
		'adminEmail' => 'test@example.com',
		'adminName' => 'Mark',
]);
Cake\Mailer\Mailer::setConfig('default', ['transport' => 'Debug']);
Cake\Mailer\TransportFactory::setConfig('Debug', [
		'className' => 'Debug',
]);

mb_internal_encoding('UTF-8');

$Tmp = new Cake\Filesystem\Folder(TMP);
$Tmp->create(TMP . 'cache/models', 0770);
$Tmp->create(TMP . 'cache/persistent', 0770);
$Tmp->create(TMP . 'cache/views', 0770);

$cache = [
	'default' => [
		'engine' => 'File',
		'path' => CACHE,
	],
	'_cake_core_' => [
		'className' => 'File',
		'prefix' => 'crud_myapp_cake_core_',
		'path' => CACHE . 'persistent/',
		'serialize' => true,
		'duration' => '+10 seconds',
	],
	'_cake_model_' => [
		'className' => 'File',
		'prefix' => 'crud_my_app_cake_model_',
		'path' => CACHE . 'models/',
		'serialize' => 'File',
		'duration' => '+10 seconds',
	],
];

Cake\Cache\Cache::setConfig($cache);

Cake\Utility\Security::setSalt('foo');

// Why is this required?
require ROOT . DS . 'config' . DS . 'bootstrap.php';

Router::defaultRouteClass(DashedRoute::class);

// Why has this no effect?
Router::reload();
require TESTS . 'config' . DS . 'routes.php';

class_alias(TestApp\Controller\AppController::class, 'App\Controller\AppController');

Cake\Core\Plugin::getCollection()->add(new Tools\Plugin());

if (getenv('db_dsn')) {
	ConnectionManager::setConfig('test', [
		'url' => getenv('db_dsn'),
		'timezone' => 'UTC',
		'quoteIdentifiers' => true,
		'cacheMetadata' => true,
	]);

	return;
}

// Ensure default test connection is defined
if (!getenv('db_class')) {
	putenv('db_dsn=sqlite:///:memory:');

	//putenv('db_dsn=postgres://postgres@127.0.0.1/test');
}

Cake\Datasource\ConnectionManager::setConfig('test', [
	'url' => getenv('db_dsn') ?: null,
	'driver' => getenv('db_class') ?: null,
	'database' => getenv('db_database') ?: null,
	'username' => getenv('db_username') ?: null,
	'password' => getenv('db_password') ?: null,
	'timezone' => 'UTC',
	'quoteIdentifiers' => true,
	'cacheMetadata' => true,
]);
