<?php

namespace Tools\Database\Type;

use Cake\Database\Type;

/**
 * Do not convert input on marshal().
 */
class ArrayType extends Type {

	/**
	 * @param mixed $value
	 * @return mixed
	 */
	public function marshal($value) {
		return $value;
	}

}
