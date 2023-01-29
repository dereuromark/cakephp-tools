<?php

namespace TestApp\Utility;

use Tools\Utility\Mime;

class TestMime extends Mime {

	/**
	 * @param bool $coreHasPrecedence
	 * @return array
	 */
	public function getMimeTypes(bool $coreHasPrecedence = false): array {
		return $this->_mimeTypesExt;
	}

}
