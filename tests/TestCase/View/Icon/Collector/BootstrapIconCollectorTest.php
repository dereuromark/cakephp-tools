<?php

namespace Tools\Test\TestCase\View\Icon\Collector;

use Cake\TestSuite\TestCase;
use Tools\View\Icon\Collector\BootstrapIconCollector;

class BootstrapIconCollectorTest extends TestCase {

	/**
	 * Show that we are still API compatible/valid.
	 *
	 * @return void
	 */
	public function testCollect(): void {
		$path = TEST_FILES . 'font_icon' . DS . 'bootstrap' . DS . 'bootstrap-icons.json';

		$result = BootstrapIconCollector::collect($path);

		$this->assertTrue(count($result) > 1360, 'count of ' . count($result));
		$this->assertTrue(in_array('info-circle-fill', $result, true));
	}

}
