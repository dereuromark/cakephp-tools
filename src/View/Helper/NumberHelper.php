<?php

namespace Tools\View\Helper;

use Cake\Utility\Hash;
use Cake\View\Helper\NumberHelper as CakeNumberHelper;

/**
 * Ovewrite to allow usage of own Number class.
 */
class NumberHelper extends CakeNumberHelper {

	/**
	 * NumberHelper::__construct()
	 *
	 * ### Settings:
	 *
	 * - `engine` Class name to use to replace Number functionality.
	 *            The class needs to be placed in the `Utility` directory.
	 *
	 * @param \Cake\View\View|null $View The View this helper is being attached to.
	 * @param array $options Configuration settings for the helper
	 * @throws \Cake\Core\Exception\Exception When the engine class could not be found.
	 */
	public function __construct($View = null, $options = []) {
		$options = Hash::merge(['engine' => 'Tools.Number'], $options);
		parent::__construct($View, $options);
	}

}
