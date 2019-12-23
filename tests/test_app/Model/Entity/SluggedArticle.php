<?php

namespace App\Model\Entity;

use Cake\ORM\Entity;

class SluggedArticle extends Entity {

	/**
	 * @var string[]
	 */
	protected $_virtual = [
		'special',
	];

	/**
	 * Virtual field
	 *
	 * @return string
	 */
	protected function _getSpecial() {
		return 'dereuromark';
	}

}
