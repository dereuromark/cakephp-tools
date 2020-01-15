<?php

namespace Tools\Error;

use Cake\Error\Debugger;
use Cake\Error\ErrorHandler as CoreErrorHandler;
use Cake\Log\Log;
use Cake\Routing\Router;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

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
 *
 * Somewhat deprecated of CakePHP 3.3+. Use Tools\Error\Middleware\ErrorHandlerMiddleware now.
 * Still needed for low level errors, though.
 */
class ErrorHandler extends CoreErrorHandler {

	use ErrorHandlerTrait;

	/**
	 * Handles exception logging
	 *
	 * @param \Throwable $exception Exception instance.
	 * @param \Psr\Http\Message\ServerRequestInterface|null $request
	 * @return bool
	 */
	public function logException(Throwable $exception, ?ServerRequestInterface $request = null): bool {
		if ($this->is404($exception)) {
			$level = LOG_ERR;
			Log::write($level, $this->buildMessage($exception), ['404']);
			return false;
		}

		return parent::logException($exception, $request ?? Router::getRequest());
	}

	/**
	 * @param \Throwable $exception
	 *
	 * @return string
	 */
	protected function buildMessage(Throwable $exception): string {
		$error = error_get_last();

		$message = sprintf(
			'%s (%s): %s in [%s, line %s]',
			$exception->getMessage(),
			$exception->getCode(),
			$error['description'] ?? '',
			$error['file'] ?? '',
			$error['line'] ?? ''
		);
		if (!empty($this->_config['trace'])) {
			/** @var string $trace */
			$trace = Debugger::trace([
				'start' => 1,
				'format' => 'log',
			]);

			$request = Router::getRequest();
			if ($request) {
				$message .= $this->getLogger()->getRequestContext($request);
			}
			$message .= "\nTrace:\n" . $trace . "\n";
		}
		$message .= "\n\n";

		return $message;
	}

}
