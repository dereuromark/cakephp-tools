<?php
App::uses('TypographyHelper', 'Tools.View/Helper');
App::uses('View', 'View');
App::uses('MyCakeTestCase', 'Tools.Lib');

/**
 * TypographyHelper Test Case
 *
 */
class TypographyHelperTest extends MyCakeTestCase {

	public $Typography;

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();

		$this->Typography = new TypographyHelper(new View(null));
	}

/**
 * tearDown method
 *
 * @return void
 */
	public function tearDown() {
		unset($this->Typography);

		parent::tearDown();
	}

/**
 * testAutoTypography method
 *
 * @return void
 */
	public function testAutoTypography() {
		$str = 'Some \'funny\' and "funky" test with a new

paragraph and a
	new line tabbed in.';

		$res = $this->Typography->autoTypography($str);
		$this->out($res);
		$this->out(h($res));
	}

/**
 * testFormatCharacter method
 *
 * @return void
 */
	public function testFormatCharacter() {

	}

/**
 * testNl2brExceptPre method
 *
 * @return void
 */
	public function testNl2brExceptPre() {

	}

}
