<?php

namespace Tools\View\Helper;

use Cake\View\Helper\NumberHelper as CakeNumberHelper;
use Cake\Utility\Hash;

/**
 * Ovewrite to allow usage of own Number class.
 */
class NumberHelper extends CakeNumberHelper {

	public function __construct($View = null, $options = array()) {
		$options = Hash::merge(array('engine' => 'Tools.Number'), $options);
		parent::__construct($View, $options);
	}

}
