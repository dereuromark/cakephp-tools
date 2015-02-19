<?php

namespace TestApp\Model\Entity;

use Cake\ORM\Entity;

class SluggedArticle extends Entity
{
	protected function _getSpecial()
	{
		return 'dereuromark';
	}
}
