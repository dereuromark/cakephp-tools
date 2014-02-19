<?php

App::uses('FormatHelper', 'Tools.View/Helper');
App::uses('MyCakeTestCase', 'Tools.TestSuite');
App::uses('HtmlHelper', 'View/Helper');
App::uses('View', 'View');

/**
 * Datetime Test Case
 */
class FormatHelperTest extends MyCakeTestCase {

	public $Format;

	public function setUp() {
		parent::setUp();

		$this->Format = new FormatHelper(new View(null));
		$this->Format->Html = new HtmlHelper(new View(null));
	}

	/**
	 */
	public function testDisabledLink() {
		$content = 'xyz';
		$data = array(
			array(),
			array('class' => 'disabledLink', 'title' => false),
			array('class' => 'helloClass', 'title' => 'helloTitle')
		);
		foreach ($data as $key => $value) {
			$res = $this->Format->disabledLink($content, $value);
			//echo ''.$res.' (\''.h($res).'\')';
			$this->assertTrue(!empty($res));
		}
	}

	/**
	 */
	public function testWarning() {
		$content = 'xyz';
		$data = array(
			true,
			false
		);
		foreach ($data as $key => $value) {
			$res = $this->Format->warning($content . ' ' . (int)$value, $value);
			//echo ''.$res.'';
			$this->assertTrue(!empty($res));
		}
	}

	/**
	 */
	public function testFontIcon() {
		$result = $this->Format->fontIcon('signin');
		$expected = '<i class="icon-signin"></i>';
		$this->assertEquals($expected, $result);

		$result = $this->Format->fontIcon('signin', array('rotate' => 90));
		$expected = '<i class="icon-signin icon-rotate-90"></i>';
		$this->assertEquals($expected, $result);

		$result = $this->Format->fontIcon('signin', array('size' => 5, 'extra' => array('muted')));
		$expected = '<i class="icon-signin icon-muted icon-5x"></i>';
		$this->assertEquals($expected, $result);
	}

	/**
	 */
	public function testOk() {
		$content = 'xyz';
		$data = array(
			true,
			false
		);
		foreach ($data as $key => $value) {
			$res = $this->Format->ok($content . ' ' . (int)$value, $value);
			//echo ''.$res.'';
			$this->assertTrue(!empty($res));
		}
	}

	public function testPriorityIcon() {
		$values = array(
			array(1, array(), '<div class="prio-low" title="prioLow">&nbsp;</div>'),
			array(2, array(), '<div class="prio-lower" title="prioLower">&nbsp;</div>'),
			array(3, array(), ''),
			array(3, array('normal' => true), '<div class="prio-normal" title="prioNormal">&nbsp;</div>'),
			array(4, array(), '<div class="prio-higher" title="prioHigher">&nbsp;</div>'),
			array(5, array(), '<div class="prio-high" title="prioHigh">&nbsp;</div>'),
			array(1, array('max' => 3), '<div class="prio-low" title="prioLow">&nbsp;</div>'),
			array(2, array('max' => 3), ''),
			array(2, array('max' => 3, 'normal' => true), '<div class="prio-normal" title="prioNormal">&nbsp;</div>'),
			array(3, array('max' => 3), '<div class="prio-high" title="prioHigh">&nbsp;</div>'),

			array(0, array('max' => 3, 'map' => array(0 => 1, 1 => 2, 2 => 3)), '<div class="prio-low" title="prioLow">&nbsp;</div>'),
			array(1, array('max' => 3, 'map' => array(0 => 1, 1 => 2, 2 => 3)), ''),
			array(2, array('max' => 3, 'map' => array(0 => 1, 1 => 2, 2 => 3)), '<div class="prio-high" title="prioHigh">&nbsp;</div>'),
		);
		foreach ($values as $key => $value) {
			$res = $this->Format->priorityIcon($value[0], $value[1]);
			//echo $key;
			//debug($res, null, false);
			$this->assertEquals($value[2], $res);
		}
	}

	/**
	 */
	public function testShortenText() {
		$data = array(
			'dfssdfsdj sdkfj sdkfj ksdfj sdkf ksdfj ksdfjsd kfjsdk fjsdkfj ksdjf ksdf jsdsdf',
			'122 jsdf sjdkf sdfj sdf',
			'ddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddd',
			'\';alert(String.fromCharCode(88,83,83))//\';alert(String.fromCharCode(88,83,83))//";alert(String.fromCharCode(88,83,83))//\";alert(String.fromCharCode(88,83,83))//--></SCRIPT>">\'><SCRIPT>alert(String.fromCharCode(88,83,83))</SCRIPT>'
		);
		foreach ($data as $key => $value) {
			$res = $this->Format->shortenText($value, 30);

			//echo '\''.h($value).'\' becomes \''.$res.'\'';
			$this->assertTrue(!empty($res));
		}
	}

	/**
	 */
	public function testTruncate() {
		$data = array(
			'dfssdfsdj sdkfj sdkfj ksdfj sdkf ksdfj ksdfjsd kfjsdk fjsdkfj ksdjf ksdf jsdsdf' => 'dfssdfsdj sdkfj sdkfj ksdfj s' . "\xe2\x80\xa6",
			'122 jsdf sjdkf sdfj sdf' => '122 jsdf sjdkf sdfj sdf',
			'ddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddd' => 'ddddddddddddddddddddddddddddd' . "\xe2\x80\xa6",
			'dsfdsf hods hfoéfh oéfhoéfhoéfhoéfhoéfhiu oéfhoéfhdüf s' => 'dsfdsf hods hfoéfh oéfhoéfhoé' . "\xe2\x80\xa6"
		);
		foreach ($data as $value => $expected) {
			$res = $this->Format->truncate($value, 30, array('html' => true));

			//debug( '\''.h($value).'\' becomes \''.$res.'\'', null, false);

			$res = $this->Format->truncate($value, 30, array('html' => true));

			//debug( '\''.h($value).'\' becomes \''.$res.'\'', null, false);

			//$this->assertTrue(!empty($res));
			$this->assertEquals($expected, $res);
		}
	}

	/**
	 */
	public function testHideEmail() {
		$mails = array(
			'test@test.de' => 't..t@t..t.de',
			'xx@yy.de' => 'x..x@y..y.de',
			'erk-wf@ve-eeervdg.com' => 'e..f@v..g.com',
		);
		foreach ($mails as $mail => $expected) {
			$res = $this->Format->hideEmail($mail);

			//echo '\''.$mail.'\' becomes \''.$res.'\' - expected \''.$expected.'\'';
			$this->assertEquals($expected, $res);
		}
	}

	/**
	 */
	public function testWordCensor() {
		$data = array(
			'dfssdfsdj sdkfj sdkfj ksdfj bitch ksdfj' => 'dfssdfsdj sdkfj sdkfj ksdfj ##### ksdfj',
			'122 jsdf ficken Sjdkf sdfj sdf' => '122 jsdf ###### Sjdkf sdfj sdf',
			'122 jsdf FICKEN sjdkf sdfjs sdf' => '122 jsdf ###### sjdkf sdfjs sdf',
			'dddddddddd ARSCH ddddddddddddd' => 'dddddddddd ##### ddddddddddddd',
			//'\';alert(String.fromCharCode(88,83,83))//\';alert(String.fromCharCode(88,83,83))//";alert(String.fromCharCode(88,83,83))//\";alert(String.fromCharCode(88,83,83))//--></SCRIPT>">\'><SCRIPT>alert(String.fromCharCode(88,83,83))</SCRIPT>' => null
		);
		foreach ($data as $value => $expected) {
			$res = $this->Format->wordCensor($value, array('Arsch', 'Ficken', 'Bitch'));

			//debug('\''.h($value).'\' becomes \''.h($res).'\'', null, false);
			$this->assertEquals($expected === null ? $value : $expected, $res);
		}
	}

	/**
	 */
/*
	public function testReverseAscii() {
		$is = $this->Format->reverseAscii('f&eacute;s');
		$expected = 'fés';
		$this->assertEquals($expected, $is);

		$is = entDec('f&eacute;s');
		$expected = 'fés';
		$this->assertEquals($expected, $is);

		$is = html_entity_decode('f&eacute;s');
		$expected = 'fés';
		$this->assertEquals($expected, $is);

		#TODO: correct it + more

	}
*/

	/**
	 */
/*
	public function testDecodeEntities() {
		$is = $this->Format->decodeEntities('f&eacute;s');
		$expected = 'fés';
		$this->assertEquals($expected, $is);

	}
*/

	public function testTab2space() {
		//echo '<h2>'.__FUNCTION__.'</h2>';

		$text = "foo\t\tfoobar\tbla\n";
		$text .= "fooo\t\tbar\t\tbla\n";
		$text .= "foooo\t\tbar\t\tbla\n";
		//echo "<pre>" . $text . "</pre>";
		//echo'becomes';
		//echo "<pre>" . $this->Format->tab2space($text) . "</pre>";

	}

	public function testArray2table() {
		//echo '<h2>'.__FUNCTION__.'</h2>';
		$array = array(
			array('x' => '0', 'y' => '0.5', 'z' => '0.9'),
			array('1', '2', '3'),
			array('4', '5', '6'),
		);

		$is = $this->Format->array2table($array);
		//echo $is;
		//$this->assertEquals($expected, $is);

		// recursive?
		$array = array(
			array('a' => array('2'), 'b' => array('2'), 'c' => array('2')),
			array(array('2'), array('2'), array('2')),
			array(array('2'), array('2'), array(array('s' => '3', 't' => '4'))),
		);

		$is = $this->Format->array2table($array, array('recursive' => true));
		//echo $is;
	}

	public function tearDown() {
		parent::tearDown();

		unset($this->Format);
	}

}
