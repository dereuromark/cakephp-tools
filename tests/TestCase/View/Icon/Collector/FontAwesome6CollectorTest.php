<?php

namespace Tools\Test\TestCase\View\Icon\Collector;

use Cake\TestSuite\TestCase;
use Tools\View\Icon\Collector\FontAwesome6IconCollector;

class FontAwesome6CollectorTest extends TestCase {

	/**
	 * Show that we are still API compatible/valid.
	 *
	 * @return void
	 */
	public function testCollect(): void {
		$path = TEST_FILES . 'font_icon' . DS . 'fa6' . DS . 'icons.json';

		$result = FontAwesome6IconCollector::collect($path);

		$this->assertTrue(count($result) > 1456, 'count of ' . count($result));
		$this->assertTrue(in_array('thumbs-up', $result, true));
	}

}
