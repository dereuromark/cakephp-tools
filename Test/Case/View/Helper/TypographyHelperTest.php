<?php
App::uses('TypographyHelper', 'Tools.View/Helper');
App::uses('View', 'View');
App::uses('MyCakeTestCase', 'Tools.TestSuite');

/**
 * TypographyHelper Test Case
 *
 */
class TypographyHelperTest extends MyCakeTestCase {

	public $Typography;

	/**
	 * SetUp method
	 *
	 * @return void
	 */
	public function setUp() {
		parent::setUp();
		Configure::delete('Typography.locale');
		Configure::write('App.language', 'eng');

		$this->Typography = new TypographyHelper(new View(null));
	}

	/**
	 * TearDown method
	 *
	 * @return void
	 */
	public function tearDown() {
		unset($this->Typography);

		parent::tearDown();
	}

	/**
	 * TestFormatCharacter method
	 *
	 * @return void
	 */
	public function testFormatCharacter() {
		$strs = array(
			'"double quotes"' => '&#8220;double quotes&#8221;',
			'"testing" in "theory" that is' => '&#8220;testing&#8221; in &#8220;theory&#8221; that is',
			"Here's what I'm" => 'Here&rsquo;s what I&rsquo;m',
			'&' => '&amp;',
			'&amp;' => '&amp;',
			'&nbsp;' => '&nbsp;',
			'--' => '&#8212;',
			'foo...' => 'foo&#8230;',
			'foo..' => 'foo..',
			'foo...bar.' => 'foo&#8230;bar.',
			'test.  new' => 'test.&nbsp; new',
		);

		foreach ($strs as $str => $expected) {
			$result = $this->Typography->formatCharacters($str);
			//debug($result);
			$this->assertEquals($expected, $result);
		}

		Configure::write('Typography.locale', 'low');
		$strs = array(
			'"double quotes"' => '&bdquo;double quotes&#8223;',
			'"testing" in "theory" that is' => '&bdquo;testing&#8223; in &bdquo;theory&#8223; that is',
			"Here's what I'm" => 'Here&rsquo;s what I&rsquo;m',
		);
		foreach ($strs as $str => $expected) {
			$result = $this->Typography->formatCharacters($str);
			//echo pre($result);
			$this->assertEquals($expected, $result);
		}

		Configure::write('Typography.locale', 'angle');
		$strs = array(
			'"double quotes"' => '&#171;double quotes&#187;',
			'"testing" in "theory" that is' => '&#171;testing&#187; in &#171;theory&#187; that is',
			"Here's what I'm" => 'Here&rsquo;s what I&rsquo;m',
		);
		foreach ($strs as $str => $expected) {
			$result = $this->Typography->formatCharacters($str);
			//echo debug($result);
			$this->assertEquals($expected, $result);
		}
	}

	/**
	 * TestAutoTypography method
	 *
	 * @return void
	 */
	public function testAutoTypography() {
		$str = 'Some \'funny\' and "funky" test';

		$result = $this->Typography->autoTypography($str);
		//echo pre($result);
		$expected = '<p>Some &#8216;funny&#8217; and &#8220;funky&#8221; test</p>';
		$this->assertEquals($expected, $result);

		Configure::write('App.language', 'deu');
		$result = $this->Typography->autoTypography($str);
		//echo pre($result);
		$expected = '<p>Some &sbquo;funny&#8219; and &bdquo;funky&#8223; test</p>';
		$this->assertEquals($expected, $result);

		Configure::write('App.language', 'fra');
		$result = $this->Typography->autoTypography($str);
		//echo debug($result);
		$expected = '<p>Some &lsaquo;funny&rsaquo; and &#171;funky&#187; test</p>';
		$this->assertEquals($expected, $result);
	}

	/**
	 * TestNl2brExceptPre method
	 *
	 * @return void
	 */
	public function testNl2brExceptPre() {
		$str = <<<EOH
Hello, I'm a happy string with some new lines.

I like to skip.

Jump

and sing.

<pre>
I am inside a pre tag. Please don't mess with me.

k?
</pre>

That's my story and I'm sticking to it.

The End.
EOH;

		$expected = <<<EOH
Hello, I'm a happy string with some new lines.<br />
<br />
I like to skip.<br />
<br />
Jump<br />
<br />
and sing.<br />
<br />
<pre>
I am inside a pre tag. Please don't mess with me.

k?
</pre><br />
<br />
That's my story and I'm sticking to it.<br />
<br />
The End.
EOH;

		$result = $this->Typography->nl2brExceptPre($str);
		$this->assertEquals($expected, $result);
	}

}
