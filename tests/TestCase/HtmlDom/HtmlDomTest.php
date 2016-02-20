<?php

namespace Tools\TestCase\HtmlDom;

use Cake\Core\Configure;
use Tools\HtmlDom\HtmlDom;
use Tools\TestSuite\TestCase;

class HtmlDomTest extends TestCase {

	public $HtmlDom = null;

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
		$html = new HtmlDom('<div id="hello">Hello</div><div id="world">World</div>');
		$result = $html->find('div', 1)->innertext;
		$this->assertSame('World', $result);
	}

}
