<?php
App::uses('ErrorHandler', 'Error');
App::uses('CakeRequest', 'Network');
App::uses('Router', 'Routing');
App::uses('Utility', 'Tools.Utility');

class MyErrorHandler extends ErrorHandler {

	/**
	 * Override core one with the following enhancements/fixes:
	 * - 404s log to a different domain
	 * - IP, Referer and Browser-Infos are added for better error debugging/tracing
	 */
	public static function handleException(Exception $exception) {
		$config = Configure::read('Exception');
		if (!empty($config['log'])) {
			$message = sprintf("[%s] %s\n%s\n%s",
				get_class($exception),
				$exception->getMessage(),
				$exception->getTraceAsString(),
				self::traceDetails()
			);
			$log = LOG_ERR;
			if (in_array(get_class($exception), array('MissingControllerException', 'MissingActionException', 'PrivateActionException', 'NotFoundException'))) {
				$log = '404';
			}
			CakeLog::write($log, $message);
		}
		$renderer = $config['renderer'];
		if ($renderer !== 'ExceptionRenderer') {
			list($plugin, $renderer) = pluginSplit($renderer, true);
			App::uses($renderer, $plugin . 'Error');
		}
		try {
			$error = new $renderer($exception);
			$error->render();
		} catch (Exception $e) {
			set_error_handler(Configure::read('Error.handler')); // Should be using configured ErrorHandler
			Configure::write('Error.trace', false); // trace is useless here since it's internal
			$message = sprintf("[%s] %s\n%s\n%s", // Keeping same message format
				get_class($e),
				$e->getMessage(),
				$e->getTraceAsString(),
				self::traceDetails()
			);
			trigger_error($message, E_USER_ERROR);
		}
	}

	/**
	 * Override core one with the following enhancements/fixes:
	 * - 404s log to a different domain
	 * - IP, Referer and Browser-Infos are added for better error debugging/tracing
	 */
	public static function handleError($code, $description, $file = null, $line = null, $context = null) {
		if (error_reporting() === 0) {
			return false;
		}
		$errorConfig = Configure::read('Error');
		list($error, $log) = self::mapErrorCode($code);
		if ($log === LOG_ERR) {
			return self::handleFatalError($code, $description, $file, $line);
		}

		$debug = Configure::read('debug');
		if ($debug) {
			$data = array(
				'level' => $log,
				'code' => $code,
				'error' => $error,
				'description' => $description,
				'file' => $file,
				'line' => $line,
				'context' => $context,
				'start' => 2,
				'path' => Debugger::trimPath($file)
			);
			return Debugger::getInstance()->outputError($data);
		} else {
			$message = $error . ' (' . $code . '): ' . $description . ' in [' . $file . ', line ' . $line . ']';
			if (!empty($errorConfig['trace'])) {
				$trace = Debugger::trace(array('start' => 1, 'format' => 'log'));
				$message .= "\nTrace:\n" . $trace . "\n";
				$message .= self::traceDetails();
			}
			return CakeLog::write($log, $message);
		}
	}

	/**
	 * Generate an error page when some fatal error happens.
	 *
	 * @param integer $code Code of error
	 * @param string $description Error description
	 * @param string $file File on which error occurred
	 * @param integer $line Line that triggered the error
	 * @return boolean
	 */
	public static function handleFatalError($code, $description, $file, $line) {
		$logMessage = 'Fatal Error (' . $code . '): ' . $description . ' in [' . $file . ', line ' . $line . ']';
		CakeLog::write(LOG_ERR, $logMessage);

		$exceptionHandler = Configure::read('Exception.handler');
		if (!is_callable($exceptionHandler)) {
			return false;
		}

		if (Configure::read('debug')) {
			return false;
		}

		if (ob_get_level()) {
			ob_end_clean();
		}

		if (Configure::read('debug')) {
			call_user_func($exceptionHandler, new FatalErrorException($description, 500, $file, $line));
		} else {
			call_user_func($exceptionHandler, new InternalErrorException());
		}
		return false;
	}

	/**
	 * Append some more infos to better track down the error
	 *
	 * @return string
	 */
	public static function traceDetails() {
		if (empty($_SERVER['REQUEST_URI']) || strpos($_SERVER['REQUEST_URI'], '/test.php?') === 0) {
			return null;
		}
		$currentUrl = Router::url(); //isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : 'n/a';
		$refererUrl = Utility::getReferer(); //Router::getRequest()->url().'
		$uid = (!empty($_SESSION) && !empty($_SESSION['Auth']['User']['id'])) ? $_SESSION['Auth']['User']['id'] : null;

		$data = array(
			@CakeRequest::clientIp(),
			$currentUrl . (!empty($refererUrl) ? (' (' . $refererUrl . ')') : ''),
			$uid,
			env('HTTP_USER_AGENT')
		);
		return implode(' - ', $data);
	}

}
