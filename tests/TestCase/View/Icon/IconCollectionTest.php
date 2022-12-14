<?php

namespace Tools\Test\TestCase\View\Icon;

use Cake\TestSuite\TestCase;
use Tools\View\Icon\FeatherIcon;
use Tools\View\Icon\IconCollection;
use Tools\View\Icon\MaterialIcon;

class IconCollectionTest extends TestCase {

	/**
	 * @return void
	 */
	public function testCollect(): void {
		$config = [
			'sets' => [
				'feather' => [
					'class' => FeatherIcon::class,
					'path' => TEST_FILES . 'font_icon/feather/icons.json',
				],
				'material' => [
					'class' => MaterialIcon::class,
					'path' => TEST_FILES . 'font_icon/material/index.d.ts',
				],
			],
		];
		$result = (new IconCollection($config))->names();
		$this->assertTrue(count($result['material']) > 1740, 'count of ' . count($result['material']));
		$this->assertTrue(in_array('zoom_out', $result['material'], true));
	}

}
