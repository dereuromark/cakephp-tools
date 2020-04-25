<?php

namespace Tools\Error;

use Cake\Core\Configure;
use Cake\Error\ErrorHandler as CoreErrorHandler;

/**
 * Custom ErrorHandler to not mix the 404 exceptions with the rest of "real" errors in the error.log file.
 *
 * All you need to do is:
 * - switch `use Cake\Error\ErrorHandler;` with `use Tools\Error\ErrorHandler;` in your bootstrap
 * - Make sure you got the 404 log defined either in your app.php or as Log::config() call.
 *
 * Example config as scoped one:
 * - 'className' => 'Cake\Log\Engine\FileLog',
 * - 'path' => LOGS,
 * - 'file'=> '404',
 * - 'levels' => ['error'],
 * - 'scopes' => ['404']
 *
 * If you don't want the errors to also show up in the debug and error log, make sure you set
 * `'scopes' => false` for those two in your app.php file.
 *
 * In case you need custom 404 mappings for some additional custom exceptions, make use of `log404` option.
 * It will overwrite the current defaults completely.
 */
class ErrorHandler extends CoreErrorHandler {

	/**
	 * Constructor
	 *
	 * @param array $config The options for error handling.
	 */
	public function __construct(array $config = []) {
		$config += (array)Configure::read('Error');
		$config += [
			'errorLogger' => ErrorLogger::class,
		];

		parent::__construct($config);
	}

}
