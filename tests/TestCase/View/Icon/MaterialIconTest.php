<?php

namespace Tools\Test\TestCase\View\Icon;

use Cake\TestSuite\TestCase;
use Tools\View\Icon\MaterialIcon;

class MaterialIconTest extends TestCase {

	/**
	 * @var \Tools\View\Icon\MaterialIcon
	 */
	protected $icon;

	/**
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		$this->icon = new MaterialIcon();
	}

	/**
	 * @return void
	 */
	public function testRender(): void {
		$result = $this->icon->render('view');
		$this->assertSame('<span class="material-symbols-outlined">view</span>', $result);
	}

}
