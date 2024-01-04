<?php

namespace Tools\Model\Enum;

use BackedEnum;

/**
 * @mixin \BackedEnum&\Cake\Database\Type\EnumLabelInterface
 */
trait EnumOptionsTrait {

	/**
	 * @param array<int|string|\BackedEnum> $cases Provide for narrowing or resorting.
	 *
	 * @return array<string, string>
	 */
	public static function options(array $cases = []): array {
		$options = [];

		if ($cases) {
			foreach ($cases as $case) {
				if (!($case instanceof BackedEnum)) {
					$case = static::from($case);
				}

				$options[(string)$case->value] = $case->label();
			}

			return $options;
		}

		$cases = static::cases();
		foreach ($cases as $case) {
			$options[(string)$case->value] = $case->label();
		}

		return $options;
	}

}
