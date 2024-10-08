<?php

namespace Tools\Error;

use Cake\Error\ErrorLogger as CoreErrorLogger;
use Cake\Log\Log;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

class ErrorLogger extends CoreErrorLogger {

	use ErrorHandlerTrait;

	/**
	 * Generate the error log message.
	 *
	 * @param \Throwable $exception The exception to log a message for.
	 * @param \Psr\Http\Message\ServerRequestInterface|null $request The current request if available.
	 * @param bool $includeTrace Should the log message include a stacktrace.
	 *
	 * @return void
	 */
	public function logException(
		Throwable $exception,
		?ServerRequestInterface $request = null,
		bool $includeTrace = false,
	): void {
		foreach ($this->getConfig('skipLog') as $class) {
			if ($exception instanceof $class) {
				return;
			}
		}

		if ($this->is404($exception)) {
			$level = LOG_ERR;
			$message = $this->getMessage($exception);

			if ($request !== null) {
				$message .= $this->getRequestContext($request);
			}

			$message .= "\n\n";

			Log::write($level, $message, ['404']);

			return;
		}

		parent::logException($exception, $request);
	}

}
