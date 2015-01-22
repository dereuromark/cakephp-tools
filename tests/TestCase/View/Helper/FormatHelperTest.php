<?php
namespace Tools\TestCase\View\Helper;

use Tools\View\Helper\FormatHelper;
use Tools\TestSuite\TestCase;
use Cake\View\View;
use Cake\Core\Configure;

/**
 * Datetime Test Case
 */
class FormatHelperTest extends TestCase {

	public $fixtures = ['core.sessions'];

	public $Format;

	public function setUp() {
		parent::setUp();

		Configure::write('App.imageBaseUrl', 'img/');

		$this->Format = new FormatHelper(new View(null));
	}

	/**
	 * @return void
	 */
	public function testDisabledLink() {
		$content = 'xyz';
		$data = [
			[],
			['class' => 'disabledLink', 'title' => false],
			['class' => 'helloClass', 'title' => 'helloTitle']
		];
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
		$data = [
			true,
			false
		];
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
		$expected = '<img src="/img/icons/edit.gif" title="' . __d('tools', 'Edit') . '" alt="[' . __d('tools', 'Edit') . ']" class="icon"/>';
		$this->assertEquals($expected, $result);
	}

	/**
	 * FormatHelperTest::testCIcon()
	 *
	 * @return void
	 */
	public function testCIcon() {
		$result = $this->Format->cIcon('edit.png');
		$expected = '<img src="/img/icons/edit.png" title="' . __d('tools', 'Edit') . '" alt="[' . __d('tools', 'Edit') . ']" class="icon"/>';
		$this->assertEquals($expected, $result);
	}

	/**
	 * FormatHelperTest::testIconWithFontIcon()
	 *
	 * @return void
	 */
	public function testIconWithFontIcon() {
		$this->Format->config('fontIcons', ['edit' => 'fa fa-pencil']);
		$result = $this->Format->icon('edit');
		$expected = '<i class="fa fa-pencil edit" title="' . __d('tools', 'Edit') . '" data-placement="bottom" data-toggle="tooltip"></i>';
		$this->assertEquals($expected, $result);
	}

	/**
	 * FormatHelperTest::testCIconWithFontIcon()
	 *
	 * @return void
	 */
	public function testCIconWithFontIcon() {
		$this->Format->config('fontIcons', ['edit' => 'fa fa-pencil']);
		$result = $this->Format->cIcon('edit.png');
		$expected = '<i class="fa fa-pencil edit" title="' . __d('tools', 'Edit') . '" data-placement="bottom" data-toggle="tooltip"></i>';
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

		$this->Format->config('fontIcons', ['edit' => 'fa fa-pencil']);

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

		$result = $this->Format->fontIcon('signin', ['rotate' => 90]);
		$expected = '<i class="fa-signin fa-rotate-90"></i>';
		$this->assertEquals($expected, $result);

		$result = $this->Format->fontIcon('signin', ['size' => 5, 'extra' => ['muted']]);
		$expected = '<i class="fa-signin fa-muted fa-5x"></i>';
		$this->assertEquals($expected, $result);

		$result = $this->Format->fontIcon('signin', ['size' => 5, 'extra' => ['muted'], 'namespace' => 'icon']);
		$expected = '<i class="icon-signin icon-muted icon-5x"></i>';
		$this->assertEquals($expected, $result);
	}

	/**
	 * @return void
	 */
	public function testYesNo() {
		$result = $this->Format->yesNo(true);
		$expected = '<img src="/img/icons/yes.gif" title="' . __d('tools', 'Yes') . '" alt=';
		$this->assertTextContains($expected, $result);

		$result = $this->Format->yesNo(false);
		$expected = '<img src="/img/icons/no.gif" title="' . __d('tools', 'No') . '" alt=';
		$this->assertTextContains($expected, $result);

		$this->Format->config('fontIcons', [
			'yes' => 'fa fa-check',
			'no' => 'fa fa-times']);

		$result = $this->Format->yesNo(true);
		$expected = '<i class="fa fa-check yes" title="' . __d('tools', 'Yes') . '" data-placement="bottom" data-toggle="tooltip"></i>';
		$this->assertEquals($expected, $result);

		$result = $this->Format->yesNo(false);
		$expected = '<i class="fa fa-times no" title="' . __d('tools', 'No') . '" data-placement="bottom" data-toggle="tooltip"></i>';
		$this->assertEquals($expected, $result);
	}


	/**
	 * @return void
	 */
	public function testOk() {
		$content = 'xyz';
		$data = [
			true,
			false
		];
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
		$this->assertNotEmpty($result);
	}

	/**
	 * FormatHelperTest::testGenderIcon()
	 *
	 * @return void
	 */
	public function testGenderIcon() {
		$result = $this->Format->genderIcon();
		$this->assertNotEmpty($result);
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
	 * FormatHelperTest::testAbsolutePaginateCount()
	 *
	 * @return void
	 */
	public function testAbsolutePaginateCount() {
		$paginator = [
			'page' => 1,
			'pageCount' => 3,
			'count' => 25,
			'limit' => 10
		];
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

		$neighbors = [
			'prev' => ['ModelName' => ['id' => 1, 'foo' => 'bar']],
			'next' => ['ModelName' => ['id' => 2, 'foo' => 'y']],
		];
		$result = $this->Format->neighbors($neighbors, 'foo');
		$expected = '<div class="next-prev-navi nextPrevNavi"><a href="/index/1" title="bar"><img src="/img/icons/prev" alt="" class="icon"/>&nbsp;prevRecord</a>&nbsp;&nbsp;<a href="/index/2" title="y"><img src="/img/icons/next" alt="" class="icon"/>&nbsp;nextRecord</a></div>';

		$this->assertEquals($expected, $result);

		$this->Format->config('fontIcons', [
			'prev' => 'fa fa-prev',
			'next' => 'fa fa-next']);
		$result = $this->Format->neighbors($neighbors, 'foo');
		$expected = '<div class="next-prev-navi nextPrevNavi"><a href="/index/1" title="bar"><i class="fa fa-prev prev" title="" data-placement="bottom" data-toggle="tooltip"></i>&nbsp;prevRecord</a>&nbsp;&nbsp;<a href="/index/2" title="y"><i class="fa fa-next next" title="" data-placement="bottom" data-toggle="tooltip"></i>&nbsp;nextRecord</a></div>';
		$this->assertEquals($expected, $result);
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
		$array = [
			['x' => '0', 'y' => '0.5', 'z' => '0.9'],
			['1', '2', '3'],
			['4', '5', '6'],
		];

		$is = $this->Format->array2table($array);
		//echo $is;
		//$this->assertEquals($expected, $is);

		// recursive?
		$array = [
			['a' => ['2'], 'b' => ['2'], 'c' => ['2']],
			[['2'], ['2'], ['2']],
			[['2'], ['2'], [['s' => '3', 't' => '4']]],
		];

		$is = $this->Format->array2table($array, ['recursive' => true]);
		//echo $is;
	}

	public function tearDown() {
		parent::tearDown();

		unset($this->Format);
	}

}
