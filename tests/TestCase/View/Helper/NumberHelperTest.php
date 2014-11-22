<?php
namespace Tools\TestCase\View\Helper;

use Tools\View\Helper\NumberHelper;
use Cake\TestSuite\TestCase;
use Cake\View\View;
use Cake\Core\Configure;
use Tools\Utility\Number;

/**
 * Number Test Case
 */
class NumberHelperTest extends TestCase {

	public function setUp() {
		parent::setUp();

		Configure::write('Localization', array(
			'decimals' => ',',
			'thousands' => '.'
		));
		Number::config();
		$this->Number = new NumberHelper(new View(null));
	}

	/**
	 * Test format
	 *
	 * TODO: move to NumberLib test?
	 *
	 * @return void
	 */
	public function testFormat() {
		$is = $this->Number->format('22');
		$expected = '22';
		$this->assertEquals($expected, $is);

		$is = $this->Number->format('22.01');
		$expected = '22,01';
		$this->assertEquals($expected, $is);

		$this->skipIf(true, 'FIXME');

		$is = $this->Number->format('22');
		$expected = '22,00';
		$this->assertEquals($expected, $is);

		$is = $this->Number->format('22.30', array('places' => 1));
		$expected = '22,3';
		$this->assertEquals($expected, $is);

		$is = $this->Number->format('22.30', array('places' => -1));
		$expected = '20';
		$this->assertEquals($expected, $is);

		$is = $this->Number->format('22.30', array('places' => -2));
		$expected = '0';
		$this->assertEquals($expected, $is);

		$is = $this->Number->format('22.30', array('places' => 3));
		$expected = '22,300';
		$this->assertEquals($expected, $is);

		$is = $this->Number->format('abc', array('places' => 2));
		$expected = '---';
		$this->assertEquals($expected, $is);

		/*
		$is = $this->Number->format('12.2', array('places'=>'a'));
		$expected = '12,20';
		$this->assertEquals($expected, $is);
		*/

		$is = $this->Number->format('22.3', array('places' => 2, 'before' => 'EUR '));
		$expected = 'EUR 22,30';
		$this->assertEquals($expected, $is);

		$is = $this->Number->format('22.3', array('places' => 2, 'after' => ' EUR'));
		$expected = '22,30 EUR';
		$this->assertEquals($expected, $is);

		$is = $this->Number->format('22.3', array('places' => 2, 'after' => 'x', 'before' => 'v'));
		$expected = 'v22,30x';
		$this->assertEquals($expected, $is);

		#TODO: more

	}

	public function tearDown() {
		parent::tearDown();

		unset($this->Number);
	}
}
