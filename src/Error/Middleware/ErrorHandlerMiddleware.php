<?php

namespace Tools\Error\Middleware;

use Cake\Core\Configure;
use Cake\Error\Middleware\ErrorHandlerMiddleware as CoreErrorHandlerMiddleware;
use Tools\Error\ExceptionTrap;

/**
 * Custom ErrorHandler to not mix the 404 exceptions with the rest of "real" errors in the error.log file.
 */
class ErrorHandlerMiddleware extends CoreErrorHandlerMiddleware {

	/**
	 * @param \Cake\Error\ExceptionTrap|array $exceptionTrap The error handler instance
	 *  or config array.
	 */
	public function __construct($exceptionTrap = []) {
		if (is_array($exceptionTrap)) {
			$exceptionTrap += (array)Configure::read('Error');
		}
		parent::__construct($exceptionTrap);

		$this->exceptionTrap = new ExceptionTrap($this->getConfig());
	}

}
