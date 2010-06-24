<?php

App::import('Lib', 'Tools.ChmodLib');

/**
 * testing
 * 2009-07-15 ms
 */
class ChmodLibCase extends CakeTestCase {
	var $Chmod = null;

	function startTest() {
		$this->Chmod = new ChmodLib();

	}

/** Start **/

	function testConvertFromOctal() {

		$is = $this->Chmod->convertFromOctal(0777);
		$expected = '777';
		$this->assertEqual($expected, $is);

		$is = $this->Chmod->convertFromOctal(0777, true);
		$expected = '0777';
		$this->assertEqual($expected, $is);

	}


	function testConvertToOctal() {

		$is = $this->Chmod->convertToOctal(777);
		$expected = 0777;
		$this->assertEqual($expected, $is);

		$is = $this->Chmod->convertToOctal('777');
		$expected = 0777;
		$this->assertEqual($expected, $is);

		$is = $this->Chmod->convertToOctal('0777');
		$expected = 0777;
		$this->assertEqual($expected, $is);
	}



	function testChmod() {
		$this->Chmod->setUser(true, true, true);
		$this->Chmod->setGroup(true, true, true);
		$this->Chmod->setOther(true, true, true);

		$is = $this->Chmod->getMode();
		$expected = 0777;
		$this->assertEqual($expected, $is);

		$is = $this->Chmod->getMode(array('type'=>'string'));
		$expected = '777';
		$this->assertEqual($expected, $is);

		$is = $this->Chmod->getMode(array('type'=>'int'));
		$expected = 777;
		$this->assertEqual($expected, $is);

		$is = $this->Chmod->getMode(array('type'=>'symbolic'));
		$expected = 'rwxrwxrwx';
		$this->assertEqual($expected, $is);
	}



/** End **/

}
?>