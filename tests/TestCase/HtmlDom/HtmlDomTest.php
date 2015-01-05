<?php

namespace Tools\TestCase\HtmlDom;

use Tools\HtmlDom\HtmlDom;
use Tools\TestSuite\TestCase;
use Cake\Core\Configure;

class HtmlDomTest extends TestCase {

	public $HtmlDom = null;

	public function setUp() {
		parent::setUp();
	}

	/**
	 * HtmlDom test
	 *
	 * @return void
	 */
	public function testBasics() {
		$html = new HtmlDom('<div id="hello">Hello</div><div id="world">World</div>');
		$result = $html->find('div', 1)->innertext;
		$this->assertSame('World', $result);
	}

}
