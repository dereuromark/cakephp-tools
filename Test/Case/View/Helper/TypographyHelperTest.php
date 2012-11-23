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
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		Configure::write('Typography.locale', '');

		$this->Typography = new TypographyHelper(new View(null));
	}

/**
 * tearDown method
 *
 * @return void
 */
	public function tearDown() {
		unset($this->Typography);

		parent::tearDown();
	}

/**
 * testFormatCharacter method
 *
 * @return void
 */
	public function testFormatCharacter() {
		$strs = array(
			'"double quotes"' => '&#8220;double quotes&#8221;',
			'"testing" in "theory" that is' => '&#8220;testing&#8221; in &#8220;theory&#8221; that is',
			"Here's what I'm" => 'Here&#8217;s what I&#8217;m',
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
			debug($result);
			$this->assertEquals($expected, $result);
		}

		//$this->tearDown();
		//$this->setUp();

		Configure::write('Typography.locale', 'low');
		$strs = array(
			'"double quotes"' 				=> '&bdquo;double quotes&#8223;',
			'"testing" in "theory" that is' => '&bdquo;testing&#8223; in &bdquo;theory&#8223; that is',
			"Here's what I'm" 				=> 'Here&#8219;s what I&#8219;m',
		);
		foreach ($strs as $str => $expected) {
			$result = $this->Typography->formatCharacters($str);
			echo pre($result);
			$this->assertEquals($expected, $result);
		}
	}

/**
 * testAutoTypography method
 *
 * @return void
 */
	public function testAutoTypography() {
		$str = 'Some \'funny\' and "funky" test with a new

paragraph and a
	new line tabbed in.';

		$res = $this->Typography->autoTypography($str);
		echo pre($res);
		debug($res);
	}

/**
 * testNl2brExceptPre method
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
