<?php

namespace Tools\Test\TestCase\View\Icon\Collector;

use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tools\View\Icon\Collector\FontAwesome4IconCollector;

class FontAwesome4CollectorTest extends TestCase {

	/**
	 * Show that we are still API compatible/valid.
	 *
	 *
	 * @param string $extension
	 *
	 * @return void
	 */
	#[DataProvider('extensions')]
	public function testCollect(string $extension): void {
		$path = TEST_FILES . 'font_icon' . DS . 'fa4' . DS . 'variables.' . $extension;

		$result = FontAwesome4IconCollector::collect($path);

		$this->assertTrue(count($result) > 780, 'count of ' . count($result));
		$this->assertTrue(in_array('thumbs-up', $result, true));
	}

	/**
	 * @return array
	 */
	public static function extensions(): array {
		return [
			'scss' => ['scss'],
			'less' => ['less'],
		];
	}

}
