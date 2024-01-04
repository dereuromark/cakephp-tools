<?php

namespace TestApp\Model\Enum;

use Cake\Database\Type\EnumLabelInterface;
use Cake\Utility\Inflector;
use Tools\Model\Enum\EnumOptionsTrait;

enum BitmaskEnum: int implements EnumLabelInterface
{
	use EnumOptionsTrait;

	case Zero = 0;
	case One = 1;
	case Two = 2;
	case Four = 4;

	/**
	 * @return string
	 */
	public function label(): string {
		return Inflector::humanize(Inflector::underscore($this->name));
	}
}
