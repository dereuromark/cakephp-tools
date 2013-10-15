<?php

App::uses('ChmodLib', 'Tools.Utility');

/**
 * testing
 */
class ChmodLibTest extends CakeTestCase {

	public $Chmod = null;

	public function setUp() {
		parent::setUp();

		$this->Chmod = new ChmodLib();
	}

/** Start **/

	public function testConvertFromOctal() {

		$is = $this->Chmod->convertFromOctal(0777);
		$expected = '777';
		$this->assertEquals($expected, $is);

		$is = $this->Chmod->convertFromOctal(0777, true);
		$expected = '0777';
		$this->assertEquals($expected, $is);
	}

	public function testConvertToOctal() {

		$is = $this->Chmod->convertToOctal(777);
		$expected = 0777;
		$this->assertEquals($expected, $is);

		$is = $this->Chmod->convertToOctal('777');
		$expected = 0777;
		$this->assertEquals($expected, $is);

		$is = $this->Chmod->convertToOctal('0777');
		$expected = 0777;
		$this->assertEquals($expected, $is);
	}

	public function testChmod() {
		$this->Chmod->setUser(true, true, true);
		$this->Chmod->setGroup(true, true, true);
		$this->Chmod->setOther(true, true, true);

		$is = $this->Chmod->getMode();
		$expected = 0777;
		$this->assertEquals($expected, $is);

		$is = $this->Chmod->getMode(array('type' => 'string'));
		$expected = '777';
		$this->assertEquals($expected, $is);

		$is = $this->Chmod->getMode(array('type' => 'int'));
		$expected = 777;
		$this->assertEquals($expected, $is);

		$is = $this->Chmod->getMode(array('type' => 'symbolic'));
		$expected = 'rwxrwxrwx';
		$this->assertEquals($expected, $is);
	}

/** End **/

}
