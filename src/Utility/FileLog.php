<?php

namespace Tools\Utility;

use ArrayAccess;
use Cake\Log\Log as CoreLog;
use Exception;
use Throwable;

/**
 * Wrapper class to log data into custom file(s).
 */
class FileLog {

	/**
	 * Debug configuration.
	 *
	 * @var mixed|null
	 */
	protected static $_debugConfig;

	/**
	 * @var array<string, mixed>|null
	 */
	protected static $_customConfig;

	/**
	 * Initialize configurations.
	 *
	 * @param string|null $filename Filename to log.
	 * @return void
	 */
	protected static function _init($filename): void {
		if ($filename === null) {
			$filename = 'custom_log';
		}

		static::$_customConfig = CoreLog::getConfig('custom');
		static::$_debugConfig = CoreLog::getConfig('debug');
		CoreLog::drop('custom');
		CoreLog::setConfig('custom', [
			'className' => 'File',
			'path' => LOGS,
			'levels' => [],
			'scopes' => ['custom'],
			'file' => $filename,
		]);

		CoreLog::drop('debug');
	}

	/**
	 * Log data into custom file
	 *
	 * @param \ArrayObject|array|string $data Data to store
	 * @param string|null $filename Filename of log file
	 * @param bool $traceKey Add trace string key into log data
	 * @return bool Success
	 */
	public static function write($data, $filename = null, $traceKey = false): bool {
		try {
			static::_init($filename);

			// Pretty print array or object
			if (is_array($data) || is_object($data)) {
				if ($traceKey) {
					if (is_object($data) && !$data instanceof ArrayAccess) {
						$data = (array)$data;
					}
					try {
						throw new Exception('Trace string', 1);
					} catch (Throwable $t) {
						$data['trace_string'] = $t->getTraceAsString();
					}
				}

				$data = print_r($data, true);
			}

			return CoreLog::write('debug', $data, ['scope' => 'custom']);
		} finally {
			static::_cleanUp();
		}
	}

	/**
	 * Drop custom log config, set default `debug` config in log registry.
	 *
	 * @return void
	 */
	protected static function _cleanUp(): void {
		CoreLog::drop('custom');

		if (static::$_customConfig !== null) {
			CoreLog::setConfig('custom', static::$_customConfig);
		}
		CoreLog::drop('debug');
		if (static::$_debugConfig !== null) {
			CoreLog::setConfig('debug', static::$_debugConfig);
		}
	}

}
