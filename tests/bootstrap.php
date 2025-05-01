<?php

use Cake\Cache\Cache;
use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\Datasource\ConnectionManager;
use Cake\Log\Log;
use Cake\Mailer\Mailer;
use Cake\Mailer\TransportFactory;
use Cake\Routing\Route\DashedRoute;
use Cake\Routing\Router;
use Cake\TestSuite\Fixture\SchemaLoader;
use Cake\Utility\Security;
use Shim\Filesystem\Folder;
use TestApp\Controller\AppController;
use Tools\ToolsPlugin;
use function Cake\Core\env;

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
define('TEST_FILES', ROOT . DS . 'tests' . DS . 'test_files' . DS);

ini_set('intl.default_locale', 'de_DE');

require_once 'vendor/autoload.php';
require_once CAKE_CORE_INCLUDE_PATH . DS . 'src' . DS . 'functions.php';

Configure::write('App', [
	'namespace' => 'TestApp',
	'encoding' => 'UTF-8',
	'fullBaseUrl' => '//localhost',
	'paths' => [
		'templates' => [
			TESTS . 'templates' . DS,
		],
	],
]);
Configure::write('debug', true);

Configure::write('Config', [
		'adminEmail' => 'test@example.com',
		'adminName' => 'Mark',
]);
Mailer::setConfig('default', ['transport' => 'Debug']);
TransportFactory::setConfig('Debug', [
		'className' => 'Debug',
]);

mb_internal_encoding('UTF-8');

$Tmp = new Folder(TMP);
$Tmp->create(TMP . 'cache/models', 0770);
$Tmp->create(TMP . 'cache/persistent', 0770);
$Tmp->create(TMP . 'cache/views', 0770);

$cache = [
	'default' => [
		'engine' => 'File',
		'path' => CACHE,
	],
	'_cake_translations_' => [
		'className' => 'File',
		'prefix' => 'myapp_cake_translations_',
		'path' => CACHE . 'persistent/',
		'serialize' => true,
		'duration' => '+10 seconds',
	],
	'_cake_model_' => [
		'className' => 'File',
		'prefix' => 'my_app_cake_model_',
		'path' => CACHE . 'models/',
		'serialize' => 'File',
		'duration' => '+10 seconds',
	],
];

Cache::setConfig($cache);

Log::setConfig('debug', [
	'className' => 'Cake\Log\Engine\FileLog',
	'path' => LOGS,
	'file' => 'debug',
	'scopes' => null,
	'levels' => ['notice', 'info', 'debug'],
	'url' => env('LOG_DEBUG_URL', null),
]);
Log::setConfig('error', [
	'className' => 'Cake\Log\Engine\FileLog',
	'path' => LOGS,
	'file' => 'error',
	'scopes' => null,
	'levels' => ['warning', 'error', 'critical', 'alert', 'emergency'],
	'url' => env('LOG_ERROR_URL', null),
]);

Security::setSalt('foo');

// Why is this required?
require ROOT . DS . 'config' . DS . 'bootstrap.php';

Router::defaultRouteClass(DashedRoute::class);

class_alias(AppController::class, 'App\Controller\AppController');

Plugin::getCollection()->add(new ToolsPlugin());

// Ensure default test connection is defined
if (!getenv('DB_URL')) {
	putenv('DB_URL=sqlite:///:memory:');
}

ConnectionManager::setConfig('test', [
	'url' => getenv('DB_URL') ?: null,
	'timezone' => 'UTC',
	'quoteIdentifiers' => true,
	'cacheMetadata' => true,
]);

if (env('FIXTURE_SCHEMA_METADATA')) {
	$loader = new SchemaLoader();
	$loader->loadInternalFile(env('FIXTURE_SCHEMA_METADATA'));
}
