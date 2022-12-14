<?php

namespace Tools\Test\TestCase\View\Helper;

use Cake\View\View;
use Shim\TestSuite\TestCase;
use Tools\View\Helper\IconHelper;
use Tools\View\Icon\FeatherIcon;
use Tools\View\Icon\MaterialIcon;

class IconHelperTest extends TestCase {

	/**
	 * @var \Tools\View\Helper\IconHelper
	 */
	protected $Icon;

	/**
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		$config = [
			'sets' => [
				'feather' => FeatherIcon::class,
				'm' => MaterialIcon::class,
			],
		];

		$this->Icon = new IconHelper(new View(null), $config);
	}

	/**
	 * @return void
	 */
	public function testIconDefault() {
		$result = $this->Icon->render('edit');
		$expected = '<span data-feather="edit" title="Edit"></span>';
		$this->assertSame($expected, $result);
	}

	/**
	 * @return void
	 */
	public function testIconPrefixed() {
		$result = $this->Icon->render('feather:edit');
		$expected = '<span data-feather="edit" title="Edit"></span>';
		$this->assertSame($expected, $result);
	}

	/**
	 * @return void
	 */
	public function testIconWithCustomAttributes() {
		$result = $this->Icon->render('m:save', [], ['data-x' => 'y']);
		$expected = '<span class="material-icons" data-x="y" title="Save">save</span>';
		$this->assertSame($expected, $result);
	}

	/**
	 * @return void
	 */
	public function testIconWithCustomClassAttributes() {
		$result = $this->Icon->render('m:save', [], ['class' => 'my-extra']);
		$expected = '<span class="material-icons my-extra" title="Save">save</span>';
		$this->assertSame($expected, $result);
	}

	/**
	 * @return void
	 */
	public function testIconWithCustomFontIcon() {
		$config = [
			'sets' => [
				'feather' => FeatherIcon::class,
				'm' => MaterialIcon::class,
			],
			'map' => [
				'edit' => 'm:save',
			],
		];

		$this->Icon = new IconHelper(new View(null), $config);

		$result = $this->Icon->render('edit');
		$expected = '<span class="material-icons" title="Save">save</span>';
		$this->assertSame($expected, $result);
	}

	/**
	 * @return void
	 */
	public function tearDown(): void {
		parent::tearDown();

		unset($this->Icon);
	}

}
