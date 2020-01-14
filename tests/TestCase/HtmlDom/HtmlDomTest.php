<?php

namespace Tools\Test\TestCase\HtmlDom;

use Shim\TestSuite\TestCase;
use Tools\HtmlDom\HtmlDom;

class HtmlDomTest extends TestCase {

	/**
	 * @var \Tools\HtmlDom\HtmlDom
	 */
	protected $HtmlDom;

	/**
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		$this->skipIf(!class_exists('Yangqi\Htmldom\Htmldom') || version_compare(PHP_VERSION, '7.3') >= 0);
	}

	/**
	 * HtmlDom test
	 *
	 * @return void
	 */
	public function testBasics() {
		$this->HtmlDom = new HtmlDom('<div id="hello">Hello</div><div id="world">World</div>');
		$result = $this->HtmlDom->find('div', 1)->innertext;
		$this->assertSame('World', $result);
	}

}
