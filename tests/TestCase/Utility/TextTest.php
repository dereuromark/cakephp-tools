<?php
App::uses('TextLib', 'Tools.Utility');

/**
 */
class TextLibTest extends CakeTestCase {

	public $TextLib;

	public function setUp() {
		parent::setUp();

		$this->TextLib = new TextLib();
	}

	public function testReadTab() {
		$data = <<<TXT
some	tabbed	data
and	another	line
TXT;
		$this->TextLib = new TextLib($data);
		$result = $this->TextLib->readTab();

		$this->assertSame(2, count($result));
		$this->assertSame(array('and', 'another', 'line'), $result[1]);
	}

	public function testReadWithPattern() {
		$data = <<<TXT
some random data
and another line
and a   third
TXT;
		$this->TextLib = new TextLib($data);
		$result = $this->TextLib->readWithPattern("%s %s %s");

		$this->assertSame(3, count($result));
		$this->assertSame(array('and', 'a', 'third'), $result[2]);
	}

	public function testConvertToOrd() {
		$this->TextLib = new TextLib('h H');
		$is = $this->TextLib->convertToOrd();
		//pr($is);
		$this->assertEquals($is, '0-104-32-72-0');

		$is = $this->TextLib->convertToOrd('x' . NL . 'x' . LF . 'x' . PHP_EOL . 'x' . CR . 'x' . TB . 'x');
		//pr($is);
	}

	public function testConvertToOrdTable() {
		$is = $this->TextLib->convertToOrdTable('x' . NL . 'x' . LF . 'x' . PHP_EOL . 'x' . CR . 'x' . TB . 'x');
		//pr($is);
	}

	public function testMaxWords() {
		$this->assertEquals('Taylor...', TextLib::maxWords('Taylor Otwell', 1));
		$this->assertEquals('Taylor___', TextLib::maxWords('Taylor Otwell', 1, array('ellipsis' => '___')));
		$this->assertEquals('Taylor Otwell', TextLib::maxWords('Taylor Otwell', 3));
	}

	public function testWords() {
		$this->TextLib = new TextLib('Hochhaus, Unter dem Bau von ae Äußeren Einflüssen - und von Autos.');
		$is = $this->TextLib->words(array('min_char' => 3));
		//pr($is);
		$this->assertTrue(!empty($is) && is_array($is) && count($is) === 9);
	}

}
