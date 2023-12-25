<?php

namespace Tools\Test\TestCase\Model\Enum;

use Shim\TestSuite\TestCase;
use TestApp\Model\Enum\FooBar;

class EnumOptionsTraitTest extends TestCase {

	/**
	 * Test partial options.
	 *
	 * @return void
	 */
	public function testEnumNarrowing() {
		$array = [
			1 => 'One',
			2 => 'Two',
		];

		$res = FooBar::options([1, 2]);
		$expected = $array;
		$this->assertSame($expected, $res);
	}

	/**
	 * @return void
	 */
	public function testEnumResorting() {
		$array = [
			2 => 'Two',
			1 => 'One',
		];

		$res = FooBar::options([FooBar::TWO, FooBar::ONE], $array);
		$expected = $array;
		$this->assertSame($expected, $res);
	}

}
