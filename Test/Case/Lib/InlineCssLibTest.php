<?php
App::uses('InlineCssLib', 'Tools.Lib');
App::uses('MyCakeTestCase', 'Tools.TestSuite');

class InlineCssLibTest extends MyCakeTestCase {

	public function setUp() {
		//Configure::write('InlineCss.engine', 'cssToInline');
		$this->InlineCss = new InlineCssLib();

		$res = App::import('Vendor', 'CssToInline', array('file' => 'css_to_inline_styles'.DS.'css_to_inline_styles.php'));
		$this->skipIf(!$res);

		parent::setUp();
	}

	public function testProcess() {
		$res = $this->InlineCss->process($this->testHtml);
		$this->debug($this->testHtml);
		$this->debug($res);
	}

	public function testProcessAlternativeEngine() {
		//TODO
	}

	public function testProcessPlainPiece() {
		$html = 'blabla
	<style>
div#container { margin: 1em auto; }
h1 { font-weight: bold; font-size: 2em; }
p { margin-bottom: 1em; font-family: sans-serif; text-align: justify; }
p.small { font-size: 70%; }
	</style>
	<div id="container">
		<h1>Sample Page Title</h1>
		<p>Bacon ipsum dolor sit amet in cow elit, in t-bone qui meatloaf corned beef aute ullamco minim. Consequat swine short ribs pastrami jerky.</p>
		<p class="small">Some small note!</p>
	</div>
bla';

		$res = $this->InlineCss->process($html);
		$this->debug($html);
		$this->debug($res);
	}

	public $testHtml = '<!doctype html>
<html lang="en">
<head>
	<style>
body { font: 11px/20px Georgia, "Times New Roman", Times, serif; }
div#container { margin: 1em auto; }
h1 { font-weight: bold; font-size: 2em; }
p { margin-bottom: 1em; font-family: sans-serif; text-align: justify; }
p.small { font-size: 70%; }
	</style>
	<title>Example</title>
</head>
<body>
	<div id="container">
		<h1>Sample Page Title</h1>
		<p>Bacon ipsum dolor sit amet in cow elit, in t-bone qui meatloaf corned beef aute ullamco minim. Consequat swine short ribs pastrami jerky.</p>
		<p class="small">Some small note!</p>
	</div>
</body>
</html>';

}
