<?php

namespace Tools\View\Icon\Collector;

use RuntimeException;

/**
 * Using e.g. "material-symbols" npm package.
 */
class MaterialIconCollector {

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

		preg_match_all('/"(.+)"/u', $content, $matches);
		if (empty($matches[1])) {
			throw new RuntimeException('Cannot parse content: ' . $filePath);
		}

		/** @var array<string> */
		return $matches[1];
	}

}
