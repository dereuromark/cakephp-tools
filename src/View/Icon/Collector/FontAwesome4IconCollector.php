<?php

namespace Tools\View\Icon\Collector;

use RuntimeException;

/**
 * Using e.g. "font-awesome" npm package.
 */
class FontAwesome4IconCollector {

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

		$ext = pathinfo($filePath, PATHINFO_EXTENSION);
		match ($ext) {
			'less' => preg_match_all('/@fa-var-([0-9a-z-]+):/', $content, $matches),
			'scss' => preg_match_all('/\$fa-var-([0-9a-z-]+):/', $content, $matches),
			default => throw new RuntimeException('Format not supported: ' . $ext),
		};

		return empty($matches[1]) ? [] : $matches[1];
	}

}
