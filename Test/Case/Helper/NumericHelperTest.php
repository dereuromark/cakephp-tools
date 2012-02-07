<?php

App::import('Helper', 'Tools.Numeric');
App::uses('MyCakeTestCase', 'Tools.Lib');
App::uses('View', 'View');
/**
 * Numeric Test Case
 *
 * @package       cake.tests
 * @subpackage    cake.tests.cases.libs.view.helpers
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
 * test cweek
 *
 * @access public
 * @return void
 * 2009-03-11 ms
 */
	public function testFormat() {
		$is = $this->Numeric->format('22');
		$expected = '22,00';
		$this->assertEquals($expected, $is);
		
		$is = $this->Numeric->format('22.30', 1);
		$expected = '22,3';
		$this->assertEquals($expected, $is);
		
		$is = $this->Numeric->format('22.30', -1);
		$expected = '20';
		$this->assertEquals($expected, $is);
		
		$is = $this->Numeric->format('22.30', -2);
		$expected = '0';
		$this->assertEquals($expected, $is);
		
		$is = $this->Numeric->format('22.30', 3);
		$expected = '22,300';
		$this->assertEquals($expected, $is);

		$is = $this->Numeric->format('abc', 2);
		$expected = '---';
		$this->assertEquals($expected, $is);
		
		$is = $this->Numeric->format('12.2', 'a');
		$expected = '12,20';
		$this->assertEquals($expected, $is);
		
		$is = $this->Numeric->format('22.3', 2, array('before'=>'EUR '));
		$expected = 'EUR 22,30';
		$this->assertEquals($expected, $is);

		$is = $this->Numeric->format('22.3', 2, array('after'=>' EUR'));
		$expected = '22,30 EUR';
		$this->assertEquals($expected, $is);
		
		$is = $this->Numeric->format('22.3', 2, array('after'=>'x','before'=>'v'));
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

