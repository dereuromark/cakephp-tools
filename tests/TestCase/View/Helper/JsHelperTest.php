<?php

namespace Tools\TestCase\View\Helper;

use Tools\View\Helper\JsHelper;
use Tools\TestSuite\TestCase;
use Cake\View\View;
use Cake\ORM\TableRegistry;
use Cake\Datasource\ConnectionManager;
use Cake\Core\Configure;

class JsHelperTest extends TestCase {

	/**
	 * @return void
	 */
	public function setUp() {
		parent::setUp();

		$this->Js = new JsHelper(new View(null));
	}

	/**
	 * @return void
	 */
	public function tearDown() {
		unset($this->Table);

 		//TableRegistry::clear();
		parent::tearDown();
	}

	/**
	 * @return void
	 */
	public function testObject() {
		$this->assertInstanceOf('Tools\View\Helper\JsHelper', $this->Js);
	}

	/**
	 * JsHelperTest::testBuffer()
	 *
	 * @return void
	 */
	public function testBuffer() {
		$script = <<<JS
jQuery(document).ready(function() {
	// Code
});
JS;
		$this->Js->buffer($script);

		$output = $this->Js->writeBuffer();

		$expected = <<<HTML
<script>
//<![CDATA[
jQuery(document).ready(function() {
	// Code
});
//]]>
</script>
HTML;
		$this->assertTextEquals($expected, $output);
	}

}
