<?php

namespace Tools\View\Helper;

use Cake\Core\App;
use Cake\Core\Exception\CakeException;
use Cake\Utility\Hash;
use Cake\View\Helper\NumberHelper as CakeNumberHelper;
use Cake\View\View;

/**
 * Overwrite to allow usage of own Number class.
 *
 * @mixin \Tools\Utility\Number
 */
class NumberHelper extends CakeNumberHelper {

	/**
	 * ### Settings:
	 *
	 * - `engine` Class name to use to replace Number functionality.
	 *            The class needs to be placed in the `Utility` directory.
	 *
	 * @param \Cake\View\View $view The View this helper is being attached to.
	 * @param array<string, mixed> $config Configuration settings for the helper
	 */
	public function __construct(View $view, array $config = []) {
		$config = Hash::merge(['engine' => 'Tools.Number'], $config);

		$engine = $config['engine'];
		$config['engine'] = 'Number';
		parent::__construct($view, $config);

		$this->setConfig('engine', $engine);
		$engineClass = App::className($engine, 'Utility');
		if ($engineClass === null) {
			throw new CakeException(sprintf('Class for %s could not be found', $engine));
		}

		$this->_engine = new $engineClass($config);
	}

}
