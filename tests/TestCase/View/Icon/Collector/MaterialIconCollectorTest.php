<?php

namespace Tools\Test\TestCase\View\Icon\Collector;

use Cake\TestSuite\TestCase;
use Tools\View\Icon\Collector\MaterialIconCollector;

class MaterialIconCollectorTest extends TestCase {

	/**
	 * Show that we are still API compatible/valid.
	 *
	 * @return void
	 */
	public function testCollect(): void {
		$path = TEST_FILES . 'font_icon' . DS . 'material' . DS . 'index.d.ts';

		$result = MaterialIconCollector::collect($path);

		$this->assertTrue(count($result) > 2444, 'count of ' . count($result));
		$this->assertTrue(in_array('zoom_in', $result, true));
	}

}
