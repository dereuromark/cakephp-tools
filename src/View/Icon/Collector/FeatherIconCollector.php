<?php

namespace Tools\View\Icon\Collector;

use RuntimeException;

/**
 * Using e.g. "feather-icons" npm package.
 */
class FeatherIconCollector {

	/**
	 * @param string $filePath
	 *
	 * @return array<string>
	 */
	public static function collect(string $filePath): array {
		$content = file_get_contents($filePath);
		if ($content === false) {
			throw new RuntimeException('Cannot read file: ' . $filePath);
		}
		$array = json_decode($content, true);
		if (!$array) {
			throw new RuntimeException('Cannot parse JSON: ' . $filePath);
		}

		/** @var array<string> */
		return array_keys($array);
	}

}
