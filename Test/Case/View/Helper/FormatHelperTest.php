<?php

App::uses('FormatHelper', 'Tools.View/Helper');
App::uses('MyCakeTestCase', 'Tools.TestSuite');
App::uses('HtmlExtHelper', 'Tools.View/Helper');
App::uses('View', 'View');

/**
 * Datetime Test Case
 */
class FormatHelperTest extends MyCakeTestCase {

	public $fixtures = array('core.cake_session');

	public $Format;

	public function setUp() {
		parent::setUp();

		$this->Format = new FormatHelper(new View(null));
		$this->Format->Html = new HtmlExtHelper(new View(null));
	}

	/**
	 * @return void
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
	 * @return void
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
	 * FormatHelperTest::testIcon()
	 *
	 * @return void
	 */
	public function testIcon() {
		$result = $this->Format->icon('edit');
		$expected = '<img src="/img/icons/edit.gif" title="' . __('Edit') . '" alt="[' . __('Edit') . ']" class="icon" />';
		$this->assertEquals($expected, $result);
	}

	/**
	 * FormatHelperTest::testCIcon()
	 *
	 * @return void
	 */
	public function testCIcon() {
		$result = $this->Format->cIcon('edit.png');
		$expected = '<img src="/img/icons/edit.png" title="' . __('Edit') . '" alt="[' . __('Edit') . ']" class="icon" />';
		$this->assertEquals($expected, $result);
	}

	/**
	 * FormatHelperTest::testIconWithFontIcon()
	 *
	 * @return void
	 */
	public function testIconWithFontIcon() {
		$this->Format->settings['fontIcons'] = array('edit' => 'fa fa-pencil');
		$result = $this->Format->icon('edit');
		$expected = '<i class="fa fa-pencil edit" title="' . __('Edit') . '" data-placement="bottom" data-toggle="tooltip"></i>';
		$this->assertEquals($expected, $result);
	}

	/**
	 * FormatHelperTest::testCIconWithFontIcon()
	 *
	 * @return void
	 */
	public function testCIconWithFontIcon() {
		$this->Format->settings['fontIcons'] = array('edit' => 'fa fa-pencil');
		$result = $this->Format->cIcon('edit.png');
		$expected = '<i class="fa fa-pencil edit" title="' . __('Edit') . '" data-placement="bottom" data-toggle="tooltip"></i>';
		$this->assertEquals($expected, $result);
	}

	/**
	 * FormatHelperTest::testSpeedOfIcons()
	 *
	 * @return void
	 */
	public function testSpeedOfIcons() {
		$count = 1000;

		$time1 = microtime(true);
		for ($i = 0; $i < $count; $i++) {
			$result = $this->Format->icon('edit');
		}
		$time2 = microtime(true);

		$this->Format->settings['fontIcons'] = array('edit' => 'fa fa-pencil');

		$time3 = microtime(true);
		for ($i = 0; $i < $count; $i++) {
			$result = $this->Format->icon('edit');
		}
		$time4 = microtime(true);

		$normalIconSpeed = number_format($time2 - $time1, 2);
		$this->debug('Normal Icons: ' . $normalIconSpeed);
		$fontIconViaStringTemplateSpeed = number_format($time4 - $time3, 2);
		$this->debug('StringTemplate and Font Icons: ' . $fontIconViaStringTemplateSpeed);
		$this->assertTrue($fontIconViaStringTemplateSpeed < $normalIconSpeed);
	}

	/**
	 * @return void
	 */
	public function testFontIcon() {
		$result = $this->Format->fontIcon('signin');
		$expected = '<i class="fa-signin"></i>';
		$this->assertEquals($expected, $result);

		$result = $this->Format->fontIcon('signin', array('rotate' => 90));
		$expected = '<i class="fa-signin fa-rotate-90"></i>';
		$this->assertEquals($expected, $result);

		$result = $this->Format->fontIcon('signin', array('size' => 5, 'extra' => array('muted')));
		$expected = '<i class="fa-signin fa-muted fa-5x"></i>';
		$this->assertEquals($expected, $result);

		$result = $this->Format->fontIcon('signin', array('size' => 5, 'extra' => array('muted'), 'namespace' => 'icon'));
		$expected = '<i class="icon-signin icon-muted icon-5x"></i>';
		$this->assertEquals($expected, $result);
	}

	/**
	 * @return void
	 */
	public function testYesNo() {
		$result = $this->Format->yesNo(true);
		$expected = '<img src="/img/icons/yes.gif" title="' . __('Yes') . '" alt=';
		$this->assertTextContains($expected, $result);

		$result = $this->Format->yesNo(false);
		$expected = '<img src="/img/icons/no.gif" title="' . __('No') . '" alt=';
		$this->assertTextContains($expected, $result);

		$this->Format->settings['fontIcons'] = array(
			'yes' => 'fa fa-check',
			'no' => 'fa fa-times');

		$result = $this->Format->yesNo(true);
		$expected = '<i class="fa fa-check yes" title="' . __('Yes') . '" data-placement="bottom" data-toggle="tooltip"></i>';
		$this->assertEquals($expected, $result);

		$result = $this->Format->yesNo(false);
		$expected = '<i class="fa fa-times no" title="' . __('No') . '" data-placement="bottom" data-toggle="tooltip"></i>';
		$this->assertEquals($expected, $result);
	}


	/**
	 * @return void
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

	/**
	 * FormatHelperTest::testThumbs()
	 *
	 * @return void
	 */
	public function testThumbs() {
		$result = $this->Format->thumbs(1);
	}

	/**
	 * FormatHelperTest::testGenderIcon()
	 *
	 * @return void
	 */
	public function testGenderIcon() {
		$result = $this->Format->genderIcon();
	}

	/**
	 * FormatHelperTest::testShowStars()
	 *
	 * @return void
	 */
	public function testShowStars() {
		$result = $this->Format->showStars(1, 3);
		$expected = '<span class="star-bar';
		$this->assertContains($expected, $result);
	}

	/**
	 * FormatHelperTest::testTextAsImage()
	 *
	 * @return void
	 */
	public function testTextAsImage() {
		$command = 'convert';
		exec($command, $a, $r);
		$this->skipIf($r !== 0, 'convert / imagick is not available');

		$result = $this->Format->textAsImage('foo bar');
		$expected = '<img src="data:image/png;base64,';
		$this->assertContains($expected, $result);
	}

	/**
	 * FormatHelperTest::testLanguageFlags()
	 *
	 * @return void
	 */
	public function testLanguageFlags() {
		$result = $this->Format->languageFlags();
		$this->debug($result);
	}

	/**
	 * FormatHelperTest::testTipHelp()
	 *
	 * @return void
	 */
	public function testTipHelp() {
		$result = $this->Format->tipHelp('foo');
		$this->debug($result);
		$expected = '<img src="/img/icons/help.gif"';
		$this->assertContains($expected, $result);
	}

	/**
	 * FormatHelperTest::testPad()
	 *
	 * @return void
	 */
	public function testPad() {
		$result = $this->Format->pad('foo bar', 20, '-');
		$expected = 'foo bar-------------';
		$this->assertEquals($expected, $result);

		$result = $this->Format->pad('foo bar', 20, '-', STR_PAD_LEFT);
		$expected = '-------------foo bar';
		$this->assertEquals($expected, $result);
	}

	/**
	 * FormatHelperTest::testOnlineIcon()
	 *
	 * @return void
	 */
	public function testOnlineIcon() {
		$result = $this->Format->onlineIcon();
		$this->debug($result);
		$expected = '<img src="/img/misc/healthbar0.gif"';
		$this->assertContains($expected, $result);
	}

	/**
	 * FormatHelperTest::testStatusLight()
	 *
	 * @return void
	 */
	public function testStatusLight() {
		$result = $this->Format->statusLight();
		$this->debug($result);
		$expected = '<img src="/img/icons/status_light_blank.gif"';
		$this->assertContains($expected, $result);
	}

	/**
	 * FormatHelperTest::testProgressBar()
	 *
	 * @return void
	 */
	public function testProgressBar() {
		$result = $this->Format->progressBar(14);
		$this->debug($result);
	}

	/**
	 * FormatHelperTest::testAbsolutePaginateCount()
	 *
	 * @return void
	 */
	public function testAbsolutePaginateCount() {
		$paginator = array(
			'page' => 1,
			'pageCount' => 3,
			'count' => 25,
			'limit' => 10
		);
		$result = $this->Format->absolutePaginateCount($paginator, 2);
		$this->debug($result);
		$this->assertEquals(2, $result);
	}

	/**
	 * FormatHelperTest::testSiteIcon()
	 *
	 * @return void
	 */
	public function testSiteIcon() {
		$result = $this->Format->siteIcon('http://www.example.org');
		$this->debug($result);
		$expected = '<img src="http://www.google.com/s2/favicons?domain=www.example.org';
		$this->assertContains($expected, $result);
	}

	/**
	 * FormatHelperTest::testEncodeEmails()
	 *
	 * @return void
	 */
	public function testEncodeEmail() {
		$result = $this->Format->encodeEmail('foobar@somedomain.com');
		$this->debug($result);
		$expected = '<span>@</span>';
		$this->assertContains($expected, $result);
	}

	/**
	 * FormatHelperTest::testEncodeEmailUrl()
	 *
	 * @return void
	 */
	public function testEncodeEmailUrl() {
		$result = $this->Format->encodeEmailUrl('foobar@somedomain.com');
		$this->debug($result);
		$expected = '<script language=javascript>';
		$this->assertContains($expected, $result);
	}

	/**
	 * FormatHelperTest::testEncodeText()
	 *
	 * @return void
	 */
	public function testEncodeText() {
		$result = $this->Format->encodeText('foobar@somedomain.com');
		$this->debug($result);
		$expected = ';&#';
		$this->assertContains($expected, $result);
	}

	/**
	 * FormatHelperTest::testConfigure()
	 *
	 * @return void
	 */
	public function testNeighbors() {
		if (!defined('ICON_PREV')) {
			define('ICON_PREV', 'prev');
		}
		if (!defined('ICON_NEXT')) {
			define('ICON_NEXT', 'next');
		}

		$neighbors = array(
			'prev' => array('ModelName' => array('id' => 1, 'foo' => 'bar')),
			'next' => array('ModelName' => array('id' => 2, 'foo' => 'y')),
		);
		$result = $this->Format->neighbors($neighbors, 'foo');
		$expected = '<div class="next-prev-navi nextPrevNavi"><a href="/index/1" title="bar"><img src="/img/icons/prev" alt="[]" class="icon" />&nbsp;prevRecord</a>&nbsp;&nbsp;<a href="/index/2" title="y"><img src="/img/icons/next" alt="[]" class="icon" />&nbsp;nextRecord</a></div>';

		$this->assertEquals($expected, $result);

		$this->Format->settings['fontIcons'] = array(
			'prev' => 'fa fa-prev',
			'next' => 'fa fa-next');
		$result = $this->Format->neighbors($neighbors, 'foo');
		$expected = '<div class="next-prev-navi nextPrevNavi"><a href="/index/1" title="bar"><i class="fa fa-prev prev" title="" data-placement="bottom" data-toggle="tooltip"></i>&nbsp;prevRecord</a>&nbsp;&nbsp;<a href="/index/2" title="y"><i class="fa fa-next next" title="" data-placement="bottom" data-toggle="tooltip"></i>&nbsp;nextRecord</a></div>';
		$this->assertEquals($expected, $result);
	}

	/**
	 * FormatHelperTest::testPriorityIcon()
	 *
	 * @return void
	 */
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
	 * @return void
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
	 * @return void
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
	 * @return void
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
	 * @return void
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
	 * FormatHelperTest::testTab2space()
	 *
	 * @return void
	 */
	public function testTab2space() {
		$text = "foo\t\tfoobar\tbla\n";
		$text .= "fooo\t\tbar\t\tbla\n";
		$text .= "foooo\t\tbar\t\tbla\n";
		$result = $this->Format->tab2space($text);
		//echo "<pre>" . $text . "</pre>";
		//echo'becomes';
		//echo "<pre>" . $result . "</pre>";
	}

	/**
	 * FormatHelperTest::testArray2table()
	 *
	 * @return void
	 */
	public function testArray2table() {
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
