<?php
App::uses('InlineCssLib', 'Tools.Lib');
App::uses('MyCakeTestCase', 'Tools.TestSuite');

class InlineCssLibTest extends MyCakeTestCase {

	public function setUp() {
		$this->InlineCss = new InlineCssLib(['engine' => InlineCssLib::ENGINE_CSS_TO_INLINE]);

		$result = App::import('Vendor', 'Tools.CssToInlineStyles', ['file' => 'CssToInlineStyles' . DS . 'CssToInlineStyles.php']);
		$this->skipIf(!$result);

		parent::setUp();
	}

	/**
	 * InlineCssLibTest::testProcess()
	 *
	 * @return void
	 */
	public function testProcess() {
		$result = $this->InlineCss->process($this->testHtml);
		$this->debug($this->testHtml);
		$this->debug($result);
	}

	/**
	 * InlineCssLibTest::testProcessPlainPiece()
	 *
	 * @return void
	 */
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

		$result = $this->InlineCss->process($html);
		$this->debug($html);
		$this->debug($result);
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

	/**
	 * InlineCssLibTest::testProcessUtf8()
	 *
	 * @return void
	 */
	public function testProcessUtf8() {
		$this->skipIf(version_compare(PHP_VERSION, '5.4.0') < 0, 'UTF8 only works with PHP5.4 and above');

		$html = 'チェック
	<style>
div#container { margin: 1em auto; }
h1 { font-weight: bold; font-size: 2em; }
p { margin-bottom: 1em; font-family: sans-serif; text-align: justify; }
p.small { font-size: 70%; }
	</style>
	<div id="container">
		<h1>チェック</h1>
		<p>降です。アーリーチェックインはリクエ</p>
		<p class="small">チェック\'foo\'</p>
	</div>
bla';
		$result = $this->InlineCss->process($html);
		$this->debug($html);
		$this->debug($result);
		$this->assertTextStartsWith('チェック', $result);
		$this->assertTextContains('<h1 style="font-size: 2em; font-weight: bold;">チェック</h1>', $result);
	}

	/**
	 * InlineCssLibTest::testProcessSpecialChars()
	 *
	 * @return void
	 */
	public function testProcessSpecialChars() {
		$this->skipIf(version_compare(PHP_VERSION, '5.4.0') < 0, 'UTF8 only works with PHP5.4 and above');

		$html = '<style>
div#container { margin: 1em auto; }
h1 { font-weight: bold; font-size: 2em; }
table tr td { margin-bottom: 1em; font-family: sans-serif; text-align: justify; }
	</style>
	<div id="container">
		<h1>&laquo;X&raquo; &amp; Y &hellip;</h1>
		<table>
			<tr>
				<td style="font-size: 0; line-height: 0;" height="20" colspan="2">&nbsp;</td>
			</tr>
		</table>
	</div>
bla';
		$result = $this->InlineCss->process($html);
		$expected = '<td style="font-family: sans-serif; margin-bottom: 1em; text-align: justify; font-size: 0; line-height: 0;" height="20" colspan="2">&nbsp;</td>';
		$this->debug($result);
		$this->assertTextContains($expected, $result);
	}

	/**
	 * InlineCssLibTest::testProcessAlternativeEngine()
	 *
	 * @return void
	 */
	public function testProcessAlternativeEngine() {
		$this->out('Emogrifier');
		$this->InlineCss = new InlineCssLib(['engine' => InlineCssLib::ENGINE_EMOGRIFIER]);
		$html = '<b>blabla</b><style>
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

		$result = $this->InlineCss->process($html);
		$this->debug($html);
		$this->debug($result);
	}

	/**
	 * InlineCssLibTest::testProcessUtf8()
	 *
	 * @return void
	 */
	public function testProcessUtf8AlternativeEngine() {
		$this->InlineCss = new InlineCssLib(['engine' => InlineCssLib::ENGINE_EMOGRIFIER]);

		$html = 'チェック
	<style>
div#container { margin: 1em auto; }
h1 { font-weight: bold; font-size: 2em; }
p { margin-bottom: 1em; font-family: sans-serif; text-align: justify; }
p.small { font-size: 70%; }
	</style>
	<div id="container">
		<h1>チェック</h1>
		<p>降です。アーリーチェックインはリクエ</p>
		<p class="small">チェック\'foo\'</p>
	</div>
bla';
		$result = $this->InlineCss->process($html);
		$this->debug($html);
		$this->debug($result);
		//$this->assertTextStartsWith('チェック', $result);
		//$this->assertTextContains('<h1 style="font-size: 2em; font-weight: bold;">チェック</h1>', $result);
	}

	/**
	 * InlineCssLibTest::testProcessSpecialChars()
	 *
	 * @return void
	 */
	public function testProcessSpecialCharsAlternativeEngine() {
		$this->InlineCss = new InlineCssLib(['engine' => InlineCssLib::ENGINE_EMOGRIFIER]);

		$html = '<style>
div#container { margin: 1em auto; }
h1 { font-weight: bold; font-size: 2em; }
table tr td { margin-bottom: 1em; font-family: sans-serif; text-align: justify; }
	</style>
	<div id="container">
		<h1>&laquo;X&raquo; &amp; Y &hellip;</h1>
		<table>
			<tr>
				<td style="font-size: 0; line-height: 0;" height="20" colspan="2">&nbsp;</td>
			</tr>
		</table>
	</div>
bla';
		$result = $this->InlineCss->process($html);
		$this->debug($result);
	}

	/**
	 * InlineCssLibTest::testProcessSpecialChars()
	 *
	 * @return void
	 */
	public function testProcessCompleteTemplateAlternativeEngine() {
		$path = CakePlugin::path('Tools') . 'Test' . DS . 'test_files' . DS . 'html' . DS;

		$this->InlineCss = new InlineCssLib(['engine' => InlineCssLib::ENGINE_EMOGRIFIER]);
		$html = file_get_contents($path . 'email_template.html');
		$result = $this->InlineCss->process($html);
		$this->debug($result);
		$expected = '<td  style="vertical-align:top;';
		$this->assertTextContains($expected, $result);

		$this->InlineCss = new InlineCssLib(['engine' => InlineCssLib::ENGINE_EMOGRIFIER]);
		$html = file_get_contents($path . 'email_template_utf8.html');
		$result = $this->InlineCss->process($html);
		$this->debug($result);
		$expected = '<table  cellspacing="0" cellpadding="0" style=\'font-family:';
		$this->assertTextContains($expected, $result);
		$this->assertTextContains('香港酒店', $result);
	}

}
