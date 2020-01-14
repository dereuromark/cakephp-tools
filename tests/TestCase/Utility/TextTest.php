<?php

namespace Tools\Test\TestCase\Utility;

use Shim\TestSuite\TestCase;
use Tools\Utility\Text;

class TextTest extends TestCase {

	/**
	 * @var \Tools\Utility\Text;
	 */
	protected $Text;

	/**
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		$this->Text = new Text();
	}

	/**
	 * @return void
	 */
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

	/**
	 * @return void
	 */
	public function testConvertToOrd() {
		$is = $this->Text->convertToOrd('h H');
		$this->assertSame($is, '0-104-32-72-0');

		$is = $this->Text->convertToOrd('x' . PHP_EOL . 'x' . PHP_EOL . 'x' . PHP_EOL . 'x' . PHP_EOL . 'x' . "\t" . 'x');
		$this->assertSame('0-120-10-120-10-120-10-120-10-120-9-120-0', $is);
	}

	/**
	 * @return void
	 */
	public function testMaxWords() {
		$this->assertEquals('Taylor...', Text::maxWords('Taylor Otwell', 1));
		$this->assertEquals('Taylor___', Text::maxWords('Taylor Otwell', 1, ['ellipsis' => '___']));
		$this->assertEquals('Taylor Otwell', Text::maxWords('Taylor Otwell', 3));
	}

	/**
	 * @return void
	 */
	public function testWords() {
		$is = $this->Text->words('Hochhaus, Unter dem Bau von ae Äußeren Einflüssen - und von Autos.', ['min_char' => 3]);
		$this->assertTrue(!empty($is) && is_array($is) && count($is) === 9);
	}

}
