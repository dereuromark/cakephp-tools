<?php
namespace Tools\TestCase\Utility;

use Tools\Utility\Text;
use Tools\TestSuite\TestCase;

/**
 */
class TextTest extends TestCase {

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
		$this->Text = new Text($data);
		$result = $this->Text->readTab();

		$this->assertSame(2, count($result));
		$this->assertSame(['and', 'another', 'line'], $result[1]);
	}

	public function testReadWithPattern() {
		$data = <<<TXT
some random data
and another line
and a   third
TXT;
		$this->Text = new Text($data);
		$result = $this->Text->readWithPattern("%s %s %s");

		$this->assertSame(3, count($result));
		$this->assertSame(['and', 'a', 'third'], $result[2]);
	}

	public function testConvertToOrd() {
		$this->Text = new Text('h H');
		$is = $this->Text->convertToOrd();
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
		$this->Text = new Text('Hochhaus, Unter dem Bau von ae Äußeren Einflüssen - und von Autos.');
		$is = $this->Text->words(['min_char' => 3]);
		//pr($is);
		$this->assertTrue(!empty($is) && is_array($is) && count($is) === 9);
	}

}
