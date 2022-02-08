<?php

namespace Tools\Error\Middleware;

use Cake\Core\Configure;
use Cake\Error\Middleware\ErrorHandlerMiddleware as CoreErrorHandlerMiddleware;
use Tools\Error\ErrorHandler;

/**
 * Custom ErrorHandler to not mix the 404 exceptions with the rest of "real" errors in the error.log file.
 */
class ErrorHandlerMiddleware extends CoreErrorHandlerMiddleware {

	/**
	 * @param \Cake\Error\ErrorHandler|array $errorHandler The error handler instance
	 *  or config array.
	 */
	public function __construct($errorHandler = []) {
		if (is_array($errorHandler)) {
			$errorHandler += (array)Configure::read('Error');
		}
		parent::__construct($errorHandler);

		$this->errorHandler = new ErrorHandler($this->getConfig());
	}

}
