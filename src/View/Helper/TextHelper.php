<?php

namespace Tools\View\Helper;

use Cake\Utility\Hash;
use Cake\View\Helper\TextHelper as CakeTextHelper;

/**
 * Ovewrite to allow usage of own Text class.
 */
class TextHelper extends CakeTextHelper {

	public function __construct($View = null, $options = array()) {
		$options = Hash::merge(array('engine' => 'Tools.Text'), $options);
		parent::__construct($View, $options);
	}

}
