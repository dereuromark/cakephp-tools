<?php

namespace Tools\Database\Type;

use Cake\Database\Driver;
use Cake\Database\Type;
use PDO;

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
