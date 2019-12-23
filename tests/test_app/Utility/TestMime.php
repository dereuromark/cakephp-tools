<?php

namespace App\Utility;

use Tools\Utility\Mime;

class TestMime extends Mime {

	/**
	 * @param bool $coreHasPrecedence
	 * @return array
	 */
	public function getMimeTypes($coreHasPrecedence = false) {
		return $this->_mimeTypesExt;
	}

}
