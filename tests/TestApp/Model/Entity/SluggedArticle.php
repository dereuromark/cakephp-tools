<?php

namespace TestApp\Model\Entity;

use Cake\ORM\Entity;

class SluggedArticle extends Entity {

	/**
	 * Virtual field
	 *
	 * @return string
	 */
	protected function _getSpecial() {
		return 'dereuromark';
	}

}
