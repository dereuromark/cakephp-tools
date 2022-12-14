<?php

namespace Tools\View\Icon\Collector;

use RuntimeException;

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
		switch ($ext) {
			case 'less':
				preg_match_all('/@fa-var-([0-9a-z-]+):/', $content, $matches);

				break;
			case 'scss':
				preg_match_all('/\$fa-var-([0-9a-z-]+):/', $content, $matches);

				break;
			default:
				throw new RuntimeException('Format not supported: ' . $ext);
		}

		$icons = !empty($matches[1]) ? $matches[1] : [];

		return $icons;
	}

}
