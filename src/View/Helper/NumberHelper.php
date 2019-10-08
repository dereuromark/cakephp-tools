<?php

namespace Tools\View\Helper;

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
	 * @param \Cake\View\View $View The View this helper is being attached to.
	 * @param array $config Configuration settings for the helper
	 * @throws \Cake\Core\Exception\Exception When the engine class could not be found.
	 */
	public function __construct(View $View, array $config = []) {
		$config = Hash::merge(['engine' => 'Tools.Number'], $config);
		parent::__construct($View, $config);
	}

}
