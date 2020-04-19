<?php

namespace Tools\IdeHelper\Generator\Task;

use Cake\Core\Configure;
use Cake\View\View;
use IdeHelper\Generator\Directive\ExpectedArguments;
use IdeHelper\Generator\Task\TaskInterface;
use Tools\View\Helper\FormatHelper;

class FormatIconTask implements TaskInterface {

	const CLASS_FORMAT_HELPER = FormatHelper::class;

	/**
	 * @return \IdeHelper\Generator\Directive\BaseDirective[]
	 */
	public function collect() {
		$result = [];

		$icons = $this->collectIcons();
		$list = [];
		foreach ($icons as $icon) {
			$list[$icon] = '\'' . $icon . '\'';
		}

		ksort($list);

		$method = '\\' . static::CLASS_FORMAT_HELPER . '::icon()';
		$directive = new ExpectedArguments($method, 0, $list);
		$result[$directive->key()] = $directive;

		return $result;
	}

	/**
	 * Fontawesome v4
	 *
	 * @return string[]
	 */
	protected function collectIcons() {
		$helper = new FormatHelper(new View());
		$configured = $helper->getConfig('fontIcons');

		$fontFile = Configure::readOrFail('Format.fontPath');
		$icons = [];
		if ($fontFile && file_exists($fontFile)) {
			$content = file_get_contents($fontFile);
			preg_match_all('/glyph-name="([a-z][^"]+)"/', $content, $matches);
			$icons = $matches[1];
			foreach ($icons as $key => $icon) {
				if (strpos($icon, 'uni') === 0 || preg_match('#[a-z]\d[a-z]\d#i', $icon)) {
					unset($icons[$key]);
				}
			}
		}

		$icons = array_merge($configured, $icons);
		sort($icons);

		return $icons;
	}

}
