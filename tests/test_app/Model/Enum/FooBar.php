<?php

namespace TestApp\Model\Enum;

use Cake\Database\Type\EnumLabelInterface;
use Cake\Utility\Inflector;
use Tools\Model\Enum\EnumOptionsTrait;

enum FooBar: int implements EnumLabelInterface
{
	use EnumOptionsTrait;

	case ZERO = 0;
	case ONE = 1;
	case TWO = 2;

	/**
	 * @return string
	 */
	public function label(): string {
		return Inflector::humanize(mb_strtolower($this->name));
	}
}
