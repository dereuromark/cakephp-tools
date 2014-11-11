<?php

namespace Tools\View\Helper;

use Cake\View\Helper\NumberHelper as CakeNumberHelper;
use Cake\Utility\Hash;
/**
 * Todo: rename to MyNumberHelper some day?
 * Aliasing it then as Number again in the project
 *
 */
class NumberHelper extends CakeNumberHelper {

	public function __construct($View = null, $options = array()) {
		$options = Hash::merge(array('engine' => 'Tools.Number'), $options);
		parent::__construct($View, $options);
	}

}
