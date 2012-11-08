<?php

App::uses('NumericHelper', 'Tools.View/Helper');
App::uses('MyCakeTestCase', 'Tools.TestSuite');
App::uses('View', 'View');
/**
 * Numeric Test Case
 *
 * @package cake.tests
 * @subpackage cake.tests.cases.libs.view.helpers
 */
class NumericHelperTest extends MyCakeTestCase {
/**
 * setUp method
 *
 * @access public
 * @return void
 */
	public function setUp() {
		$this->Numeric = new NumericHelper(new View(null));
	}


/**
 * test format
 *
 * TODO: move to NumberLib test?
 *
 * @access public
 * @return void
 * 2009-03-11 ms
 */
	public function testFormat() {
		$is = $this->Numeric->format('22');
		$expected = '22,00';
		$this->assertEquals($expected, $is);

		$is = $this->Numeric->format('22.30', array('places'=>1));
		$expected = '22,3';
		$this->assertEquals($expected, $is);

		$is = $this->Numeric->format('22.30', array('places'=>-1));
		$expected = '20';
		$this->assertEquals($expected, $is);

		$is = $this->Numeric->format('22.30', array('places'=>-2));
		$expected = '0';
		$this->assertEquals($expected, $is);

		$is = $this->Numeric->format('22.30', array('places'=>3));
		$expected = '22,300';
		$this->assertEquals($expected, $is);

		$is = $this->Numeric->format('abc', array('places'=>2));
		$expected = '---';
		$this->assertEquals($expected, $is);

		/*
		$is = $this->Numeric->format('12.2', array('places'=>'a'));
		$expected = '12,20';
		$this->assertEquals($expected, $is);
		*/

		$is = $this->Numeric->format('22.3', array('places'=>2, 'before'=>'EUR '));
		$expected = 'EUR 22,30';
		$this->assertEquals($expected, $is);

		$is = $this->Numeric->format('22.3', array('places'=>2, 'after'=>' EUR'));
		$expected = '22,30 EUR';
		$this->assertEquals($expected, $is);

		$is = $this->Numeric->format('22.3', array('places'=>2, 'after'=>'x','before'=>'v'));
		$expected = 'v22,30x';
		$this->assertEquals($expected, $is);

		#TODO: more

	}


/**
 * tearDown method
 *
 * @access public
 * @return void
 */
	public function tearDown() {
		unset($this->Numeric);
	}
}

