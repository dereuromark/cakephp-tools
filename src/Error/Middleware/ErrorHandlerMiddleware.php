<?php

namespace Tools\Error\Middleware;

use Cake\Core\Configure;
use Cake\Error\Middleware\ErrorHandlerMiddleware as CoreErrorHandlerMiddleware;
use Cake\Log\Log;

/**
 * Error handling middleware.
 *
 * Custom ErrorHandler to not mix the 404 exceptions with the rest of "real" errors in the error.log file.
 *
 * All you need to do is in your Application.php file for `new ErrorHandlerMiddleware()`:
 * - Switch `use Cake\Error\Middleware\ErrorHandlerMiddleware;` with `use Tools\Error\Middleware\ErrorHandlerMiddleware;`
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
class ErrorHandlerMiddleware extends CoreErrorHandlerMiddleware {

	/**
	 * @param string|callable|null $renderer The renderer or class name
	 *   to use or a callable factory.
	 * @param array $config Configuration options to use. If empty, `Configure::read('Error')`
	 *   will be used.
	 */
	public function __construct($renderer = null, array $config = []) {
		// Only needed until CakePHP 3.4+ for BC reasons.
		if ($renderer === null) {
			$renderer = Configure::read('Error.exceptionRenderer');
		}

		parent::__construct($renderer, $config);
	}

	/**
	 * Log an error for the exception if applicable.
	 *
	 * @param \Psr\Http\Message\ServerRequestInterface $request The current request.
	 * @param \Exception $exception The exception to log a message for.
	 * @return void
	 */
	protected function logException($request, $exception) {
		$blacklist = [
			'Cake\Routing\Exception\MissingControllerException',
			'Cake\Routing\Exception\MissingActionException',
			'Cake\Routing\Exception\PrivateActionException',
			'Cake\Routing\Exception\NotFoundException',
			'Cake\Datasource\Exception\RecordNotFoundException',
			'Cake\Network\Exception\MethodNotAllowedException',
			'Cake\Network\Exception\BadRequestException',
			'Cake\Network\Exception\ForbiddenException',
			'Cake\Network\Exception\GoneException',
			'Cake\Network\Exception\ConflictException',
			'Cake\Network\Exception\InvalidCsrfToken',
			'Cake\Network\Exception\UnauthorizedException',
			'Cake\Network\Exception\NotAcceptableException',
		];
		if (isset($this->_config['log404'])) {
			$blacklist = $this->_config['log404'];
		}
		if ($blacklist && in_array(get_class($exception), (array)$blacklist)) {
			$level = LOG_ERR;
			Log::write($level, $this->getMessage($request, $exception), ['404']);
			return;
		}

		parent::logException($request, $exception);
	}

}
