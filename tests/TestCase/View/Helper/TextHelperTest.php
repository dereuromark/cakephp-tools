<?php
namespace Tools\TestCase\View\Helper;

use Tools\View\Helper\TextHelper;
use Tools\TestSuite\TestCase;
use Cake\View\View;
use Cake\Core\Configure;

/**
 * DateText Test Case
 *
 */
class TextHelperTest extends TestCase {

	public function setUp() {
		parent::setUp();

		$this->Text = new TextHelper(new View(null));
	}

	/**
	 * TearDown method
	 *
	 * @return void
	 */
	public function tearDown() {
		parent::tearDown();

		unset($this->Text);
	}

	/**
	 * Test calling Utility.Text class
	 *
	 * @return void
	 */
	public function testParentCall() {
		$result = $this->Text->abbreviate('FooBar');
		$this->assertSame('FooBar', $result);
	}

}
