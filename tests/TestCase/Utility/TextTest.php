<?php

namespace Tools\TestCase\Utility;

use Tools\TestSuite\TestCase;
use Tools\Utility\Text;

/**
 */
class TextTest extends TestCase {

	/**
	 * @var \Tools\Utility\Text;
	 */
	public $Text;

	public function setUp() {
		parent::setUp();

		$this->Text = new Text();
	}

	public function testReadTab() {
		$data = <<<TXT
some	tabbed	data
and	another	line
TXT;
		$result = $this->Text->readTab($data);

		$this->assertSame(2, count($result));
		$this->assertSame(['and', 'another', 'line'], $result[1]);
	}

	/**
	 * @return void
	 */
	public function testReadWithPattern() {
		$data = <<<TXT
some random data
and another line
and a   third
TXT;
		$result = $this->Text->readWithPattern($data, '%s %s %s');

		$this->assertSame(3, count($result));
		$this->assertSame(['and', 'a', 'third'], $result[2]);
	}

	public function testConvertToOrd() {
		$is = $this->Text->convertToOrd('h H');
		//pr($is);
		$this->assertEquals($is, '0-104-32-72-0');

		$is = $this->Text->convertToOrd('x' . PHP_EOL . 'x' . PHP_EOL . 'x' . PHP_EOL . 'x' . PHP_EOL . 'x' . "\t" . 'x');
		//pr($is);
	}

	public function testConvertToOrdTable() {
		$is = $this->Text->convertToOrdTable('x' . PHP_EOL . 'x' . PHP_EOL . 'x' . PHP_EOL . 'x' . PHP_EOL . 'x' . "\t" . 'x');
		//pr($is);
	}

	public function testMaxWords() {
		$this->assertEquals('Taylor...', Text::maxWords('Taylor Otwell', 1));
		$this->assertEquals('Taylor___', Text::maxWords('Taylor Otwell', 1, ['ellipsis' => '___']));
		$this->assertEquals('Taylor Otwell', Text::maxWords('Taylor Otwell', 3));
	}

	public function testWords() {
		$is = $this->Text->words('Hochhaus, Unter dem Bau von ae Äußeren Einflüssen - und von Autos.', ['min_char' => 3]);
		$this->assertTrue(!empty($is) && is_array($is) && count($is) === 9);
	}

}
