<?php

namespace Tools\TestCase\HtmlDom;

use Tools\HtmlDom\HtmlDom;
use Tools\TestSuite\TestCase;

class HtmlDomTest extends TestCase {

	/**
	 * @var \Tools\HtmlDom\HtmlDom
	 */
	public $HtmlDom;

	public function setUp() {
		parent::setUp();

		$this->skipIf(!class_exists('Yangqi\Htmldom\Htmldom'));
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
