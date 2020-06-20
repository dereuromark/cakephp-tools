<?php

namespace Tools\Utility;

use Cake\Log\Log as CoreLog;
use Exception;

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
	 * Initialize configurations.
	 *
	 * @param string $filename Filename to log.
	 * @return void
	 */
	protected static function _init($filename) {
		if ($filename === null) {
			$filename = 'custom_log';
		}

		CoreLog::setConfig('custom', [
			'className' => 'File',
			'path' => LOGS,
			'levels' => [],
			'scopes' => ['custom'],
			'file' => $filename,
		]);

		static::$_debugConfig = CoreLog::getConfig('debug');

		CoreLog::drop('debug');
	}

	/**
	 * Log data into custom file
	 *
	 * @param array|string $data Data to store
	 * @param string|null $filename Filename of log file
	 * @param bool $traceKey Add trace string key into log data
	 * @return bool Success
	 */
	public static function write($data, $filename = null, $traceKey = false) {
		static::_init($filename);

		// Pretty print array or object
		if (is_array($data) || is_object($data)) {
			if ($traceKey) {
				try {
					throw new Exception('Trace string', 1);
				} catch (\Throwable $t) {
					$data['trace_string'] = $t->getTraceAsString();
				}
			}

			$data = print_r($data, true);
		}

		$logged = CoreLog::write('debug', $data, ['scope' => 'custom']);

		static::_cleanUp();

		return $logged;
	}

	/**
	 * Drop custom log config, set default `debug` config in log registry.
	 *
	 * @return void
	 */
	protected static function _cleanUp() {
		CoreLog::drop('custom');

		CoreLog::setConfig('debug', static::$_debugConfig);
	}

}
