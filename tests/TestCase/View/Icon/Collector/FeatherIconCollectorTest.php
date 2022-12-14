<?php

namespace Tools\Test\TestCase\View\Icon\Collector;

use Cake\TestSuite\TestCase;
use Tools\View\Icon\Collector\FeatherIconCollector;

class FeatherIconCollectorTest extends TestCase {

	/**
	 * Show that we are still API compatible/valid.
	 *
	 * @return void
	 */
	public function testCollect(): void {
		$path = TEST_FILES . 'font_icon' . DS . 'feather' . DS . 'icons.json';

		$result = FeatherIconCollector::collect($path);

		$this->assertTrue(count($result) > 280, 'count of ' . count($result));
		$this->assertTrue(in_array('zoom-in', $result, true));
	}

}
