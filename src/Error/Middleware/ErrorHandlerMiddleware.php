<?php

namespace Tools\Error\Middleware;

use Cake\Controller\Exception\MissingActionException;
use Cake\Datasource\Exception\InvalidPrimaryKeyException;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Error\Middleware\ErrorHandlerMiddleware as CoreErrorHandlerMiddleware;
use Cake\Log\Log;
use Cake\Network\Exception\BadRequestException;
use Cake\Network\Exception\ConflictException;
use Cake\Network\Exception\GoneException;
use Cake\Network\Exception\InvalidCsrfTokenException;
use Cake\Network\Exception\MethodNotAllowedException;
use Cake\Network\Exception\NotAcceptableException;
use Cake\Network\Exception\NotFoundException;
use Cake\Network\Exception\UnauthorizedException;
use Cake\Network\Exception\UnavailableForLegalReasonsException;
use Cake\Routing\Exception\MissingControllerException;
use Cake\Routing\Exception\MissingRouteException;
use Cake\View\Exception\MissingViewException;

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
	 * @var array
	 */
	public static $blacklist = [
		InvalidPrimaryKeyException::class,
		NotFoundException::class,
		MethodNotAllowedException::class,
		NotAcceptableException::class,
		RecordNotFoundException::class,
		BadRequestException::class,
		GoneException::class,
		ConflictException::class,
		InvalidCsrfTokenException::class,
		UnauthorizedException::class,
		MissingControllerException::class,
		MissingActionException::class,
		MissingRouteException::class,
		MissingViewException::class,
		UnavailableForLegalReasonsException::class,
	];

	/**
	 * @param string|callable|null $renderer The renderer or class name
	 *   to use or a callable factory.
	 * @param array $config Configuration options to use. If empty, `Configure::read('Error')`
	 *   will be used.
	 */
	public function __construct($renderer = null, array $config = []) {
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
		$blacklist = static::$blacklist;
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
