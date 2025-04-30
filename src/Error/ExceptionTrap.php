<?php

namespace Tools\Error;

use Cake\Core\Configure;
use Cake\Core\Exception\CakeException;
use Cake\Error\Debugger;
use Cake\Error\ExceptionTrap as CoreExceptionTrap;
use Cake\Http\ServerRequest;
use Cake\Log\Log;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

/**
 * Custom ErrorHandler to not mix the 404 exceptions with the rest of "real" errors in the error.log file.
 *
 * All you need to do is:
 * - switch `use Cake\Error\ExceptionTrap;` with `use Tools\Error\ExceptionTrap;` in your bootstrap
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
class ExceptionTrap extends CoreExceptionTrap {

	use ErrorHandlerTrait;

	/**
	 * Constructor
	 *
	 * @param array $config The options for error handling.
	 */
	public function __construct(array $config = []) {
		$config += (array)Configure::read('Error');
		$config += [
			'logger' => ErrorLogger::class,
		];

		parent::__construct($config);
	}

	/**
	 * Log an error for the exception if applicable.
	 *
	 * @param \Exception $exception The exception to log a message for.
	 * @param \Psr\Http\Message\ServerRequestInterface|null $request The current request.
	 *
	 * @return void
	 */
	public function logException(Throwable $exception, ?ServerRequestInterface $request = null): void {
		$shouldLog = $this->_config['log'];
		if ($shouldLog) {
			foreach ($this->getConfig('skipLog') as $class) {
				if ($exception instanceof $class) {
					$shouldLog = false;
					break;
				}
			}
		}
		if (!$shouldLog) {
			return;
		}

		if ($this->is404($exception, $request)) {
			$level = LOG_ERR;
			$message = $this->getMessage($exception);
			if ($request !== null) {
				$message .= $this->getRequestContext($request);
			}

			Log::write($level, $message, ['404']);

			return;
		}

		$this->logger()->logException($exception, $request, $this->_config['trace']);
	}

	/**
	 * Generate the message for the exception
	 *
	 * @param \Throwable $exception The exception to log a message for.
	 * @param bool $isPrevious False for original exception, true for previous
	 * @param bool $includeTrace Whether to include a stack trace.
	 * @return string Error message
	 */
	protected function getMessage(Throwable $exception, bool $isPrevious = false, bool $includeTrace = false): string {
		$message = sprintf(
			'%s[%s] %s in %s on line %s',
			$isPrevious ? "\nCaused by: " : '',
			$exception::class,
			$exception->getMessage(),
			$exception->getFile(),
			$exception->getLine(),
		);

		$debug = Configure::read('debug');
		if ($debug && $exception instanceof CakeException) {
			$attributes = $exception->getAttributes();
			if ($attributes) {
				$message .= "\nException Attributes: " . var_export($exception->getAttributes(), true);
			}
		}

		if ($includeTrace) {
			$trace = Debugger::formatTrace($exception, ['format' => 'points']);
			assert(is_array($trace));
			$message .= "\nStack Trace:\n";
			foreach ($trace as $line) {
				if (is_string($line)) {
					$message .= '- ' . $line;
				} else {
					$message .= "- {$line['file']}:{$line['line']}\n";
				}
			}
		}

		$previous = $exception->getPrevious();
		if ($previous) {
			$message .= $this->getMessage($previous, true, $includeTrace);
		}

		return $message;
	}

	/**
	 * Get the request context for an error/exception trace.
	 *
	 * @param \Psr\Http\Message\ServerRequestInterface $request The request to read from.
	 * @return string
	 */
	protected function getRequestContext(ServerRequestInterface $request): string {
		$message = "\nRequest URL: " . $request->getRequestTarget();

		$referer = $request->getHeaderLine('Referer');
		if ($referer) {
			$message .= "\nReferer URL: " . $referer;
		}

		if ($request instanceof ServerRequest) {
			$clientIp = $request->clientIp();
			if ($clientIp && $clientIp !== '::1') {
				$message .= "\nClient IP: " . $clientIp;
			}
		}

		return $message;
	}

}
