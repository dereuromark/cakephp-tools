<?php

namespace Tools\Test\TestCase\View\Icon;

use Cake\TestSuite\TestCase;
use Tools\View\Icon\BootstrapIcon;

class BootstrapIconTest extends TestCase {

	/**
	 * @var \Tools\View\Icon\BootstrapIcon
	 */
	protected $icon;

	/**
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		$this->icon = new BootstrapIcon();
	}

	/**
	 * @return void
	 */
	public function testRender(): void {
		$result = $this->icon->render('info-circle-fill');
		$this->assertSame('<span class="bi bi-info-circle-fill"></span>', $result);
	}

}
