<?php

namespace TestApp\Model\Entity;

use Cake\ORM\Entity;

class SluggedArticle extends Entity {

	/**
	 * @var array<string>
	 */
	protected array $_virtual = [
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
