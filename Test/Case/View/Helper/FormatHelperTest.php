<?php

App::uses('FormatHelper', 'Tools.View/Helper');
App::uses('MyCakeTestCase', 'Tools.TestSuite');
App::uses('HtmlHelper', 'View/Helper');
App::uses('View', 'View');

/**
 * Datetime Test Case
 *
 * @package cake.tests
 * @subpackage cake.tests.cases.libs.view.helpers
 */
class FormatHelperTest extends MyCakeTestCase {
	/**
	* setUp method

	* @access public
	* @return void
	*/
	public function setUp() {
		$this->Format = new FormatHelper(new View(null));
		$this->Format->Html = new HtmlHelper(new View(null));
	}


	/**
	 * 2009-08-30 ms
	 */
	public function testDisabledLink() {
		$content = 'xyz';
		$data = array(
			array(),
			array('class'=>'disabledLink', 'title'=>false),
			array('class'=>'helloClass', 'title'=>'helloTitle')
		);
		foreach ($data as $key => $value) {
			$res = $this->Format->disabledLink($content, $value);
			echo ''.$res.' (\''.h($res).'\')';
			$this->assertTrue(!empty($res));
		}
	}


	/**
	 * 2009-08-30 ms
	 */
	public function testWarning() {
		$content = 'xyz';
		$data = array(
			true,
			false
		);
		foreach ($data as $key => $value) {
			$res = $this->Format->warning($content.' '.(int)$value, $value);
			echo ''.$res.'';
			$this->assertTrue(!empty($res));
		}
	}


	/**
	 * 2009-08-30 ms
	 */
	public function testOk() {
		$content = 'xyz';
		$data = array(
			true,
			false
		);
		foreach ($data as $key => $value) {
			$res = $this->Format->ok($content.' '.(int)$value, $value);
			echo ''.$res.'';
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
			echo $key;
			//debug($res, null, false); ob_flush();
			$this->assertEquals($value[2], $res);
		}
	}

	/**
	 * 2009-08-30 ms
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

			echo '\''.h($value).'\' becomes \''.$res.'\'';
			$this->assertTrue(!empty($res));
		}

	}

	/**
	 * 2009-08-30 ms
	 */
	public function testTruncate() {
		$data = array(
			'dfssdfsdj sdkfj sdkfj ksdfj sdkf ksdfj ksdfjsd kfjsdk fjsdkfj ksdjf ksdf jsdsdf' => 'dfssdfsdj sdkfj sdkfj k&#8230;',
			'122 jsdf sjdkf sdfj sdf' => '122 jsdf sjdkf sdfj sdf',
			'ddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddd' => 'ddddddddddddddddddddddd&#8230;',
			'dsfdsf hods hfoéfh oéfhoéfhoéfhoéfhoéfhiu oéfhoéfhdüf s' => 'dsfdsf hods hfoéfh oéfh&#8230;'
		);
		foreach ($data as $value => $expected) {
			$res = $this->Format->truncate($value, 30);

			debug( '\''.h($value).'\' becomes \''.$res.'\'', null, false); ob_flush();

			$res = $this->Format->truncate($value, 30, array('html' => true));

			debug( '\''.h($value).'\' becomes \''.$res.'\'', null, false); ob_flush();

			//$this->assertTrue(!empty($res));
			$this->assertEquals($expected, $res);
		}

	}

	/**
	 * 2009-08-30 ms
	 */
	public function testHideEmail() {
		$mails = array(
			'test@test.de' => 't..t@t..t.de',
			'xx@yy.de' => 'x..x@y..y.de',
			'erk-wf@ve-eeervdg.com' => 'e..f@v..g.com',
		);
		foreach ($mails as $mail => $expected) {
			$res = $this->Format->hideEmail($mail);

			echo '\''.$mail.'\' becomes \''.$res.'\' - expected \''.$expected.'\'';
			$this->assertEquals($expected, $res);
		}

	}



	/**
	 * 2009-08-30 ms
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

			//debug('\''.h($value).'\' becomes \''.h($res).'\'', null, false); ob_flush();
			$this->assertEquals($expected === null ? $value : $expected, $res);
		}

	}


/**
 * 2009-03-11 ms
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
	 * 2009-03-11 ms
	 */
/*
	public function testDecodeEntities() {
		$is = $this->Format->decodeEntities('f&eacute;s');
		$expected = 'fés';
		$this->assertEquals($expected, $is);

	}
*/

	public function testTab2space() {
		echo '<h2>'.__FUNCTION__.'</h2>';

		$text = "foo\t\tfoobar\tbla\n";
		$text .= "fooo\t\tbar\t\tbla\n";
		$text .= "foooo\t\tbar\t\tbla\n";
		echo "<pre>" . $text . "</pre>";
		echo'becomes';
		echo "<pre>" . $this->Format->tab2space($text) . "</pre>";

	}


	public function testArray2table() {
		echo '<h2>'.__FUNCTION__.'</h2>';
		$array = array(
			array('x'=>'0', 'y'=>'0.5', 'z'=>'0.9'),
			array('1', '2', '3'),
			array('4', '5', '6'),
		);

		$is = $this->Format->array2table($array);
		echo $is;
		//$this->assertEquals($expected, $is);

		# recursive?
		$array = array(
			array('a'=>array('2'), 'b'=>array('2'), 'c'=>array('2')),
			array(array('2'), array('2'), array('2')),
			array(array('2'), array('2'), array(array('s'=>'3', 't'=>'4'))),
		);

		$is = $this->Format->array2table($array, array('recursive'=>true));
		echo $is;
	}

/**
 * tearDown method
 *
 * @access public
 * @return void
 */
	public function tearDown() {
		unset($this->Format);
	}
}