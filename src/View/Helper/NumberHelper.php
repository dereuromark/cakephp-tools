<?php

namespace Tools\View\Helper;

use Cake\Utility\Hash;
use Cake\View\Helper\NumberHelper as CakeNumberHelper;
use Cake\View\View;
use Tools\Utility\Number;

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
	 * @param array<string, mixed> $config Configuration settings for the helper
	 */
	public function __construct(View $View, array $config = []) {
		$config = Hash::merge(['engine' => 'Tools.Number'], $config);

		parent::__construct($View, $config);
	}
	/**
	 * Call methods from Cake\I18n\Number utility class
	 *
	 * @param string $method Method to invoke
	 * @param array $params Array of params for the method.
	 * @return mixed Whatever is returned by called method, or false on failure
	 */
	public function __call(string $method, array $params): mixed {
		return Number::{$method}(...$params);
	}

}
