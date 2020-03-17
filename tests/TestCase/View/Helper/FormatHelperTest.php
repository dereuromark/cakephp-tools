<?php

namespace Tools\Test\TestCase\View\Helper;

use Cake\Core\Configure;
use Cake\Routing\Router;
use Cake\View\View;
use Shim\TestSuite\TestCase;
use Tools\Utility\Text;
use Tools\View\Helper\FormatHelper;

/**
 * Datetime Test Case
 */
class FormatHelperTest extends TestCase {

	/**
	 * @var string[]
	 */
	protected $fixtures = [
		'core.Sessions',
	];

	/**
	 * @var \Tools\View\Helper\FormatHelper
	 */
	protected $Format;

	/**
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		Router::reload();
		Router::connect('/:controller', ['action' => 'index']);
		Router::connect('/:controller/:action/*');

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
			['class' => 'helloClass', 'title' => 'helloTitle'],
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
			false,
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
		$expected = '<i class="icon icon-edit fa fa-pencil" title="' . __d('tools', 'Edit') . '" data-placement="bottom" data-toggle="tooltip"></i>';
		$this->assertEquals($expected, $result);
	}

	/**
	 * FormatHelperTest::testIconWithFontIcon()
	 *
	 * @return void
	 */
	public function testIconWithCustomAttributes() {
		$result = $this->Format->icon('edit', [], ['data-x' => 'y']);
		$expected = '<i class="icon icon-edit fa fa-pencil" data-x="y" title="' . __d('tools', 'Edit') . '" data-placement="bottom" data-toggle="tooltip"></i>';
		$this->assertEquals($expected, $result);
	}

	/**
	 * @return void
	 */
	public function testIconWithCustomClassAttributes() {
		$result = $this->Format->icon('edit', [], ['class' => 'my-extra']);
		$expected = '<i class="icon icon-edit fa fa-pencil my-extra" title="' . __d('tools', 'Edit') . '" data-placement="bottom" data-toggle="tooltip"></i>';
		$this->assertEquals($expected, $result);
	}

	/**
	 * @return void
	 */
	public function testIconWithCustomFontIcon() {
		$this->Format->setConfig('fontIcons', ['edit' => 'fax fax-pen']);
		$result = $this->Format->icon('edit');
		$expected = '<i class="icon icon-edit fax fax-pen" title="' . __d('tools', 'Edit') . '" data-placement="bottom" data-toggle="tooltip"></i>';
		$this->assertEquals($expected, $result);
	}

	/**
	 * @return void
	 */
	public function testIconWithPrefixedIcon() {
		$this->Format->setConfig('iconNamespaces', ['fa', 'glyphicon']);
		$result = $this->Format->icon('glyphicon-foo');
		$expected = '<i class="icon icon-glyphicon-foo glyphicon glyphicon-foo" title="' . __d('tools', 'Foo') . '" data-placement="bottom" data-toggle="tooltip"></i>';
		$this->assertEquals($expected, $result);

		$result = $this->Format->icon('glyphicon-edit');
		$expected = '<i class="icon icon-glyphicon-edit glyphicon glyphicon-edit" title="' . __d('tools', 'Edit') . '" data-placement="bottom" data-toggle="tooltip"></i>';
		$this->assertEquals($expected, $result);
	}

	/**
	 * @return void
	 */
	public function testFontIcon() {
		$result = $this->Format->fontIcon('signin');
		$expected = '<i class="fa fa-signin"></i>';
		$this->assertEquals($expected, $result);

		$result = $this->Format->fontIcon('signin', ['rotate' => 90]);
		$expected = '<i class="fa fa-signin fa-rotate-90"></i>';
		$this->assertEquals($expected, $result);

		$result = $this->Format->fontIcon('signin', ['size' => 5, 'extra' => ['muted']]);
		$expected = '<i class="fa fa-signin fa-muted fa-5x"></i>';
		$this->assertEquals($expected, $result);

		$result = $this->Format->fontIcon('signin', ['size' => 5, 'extra' => ['muted'], 'namespace' => 'myicon']);
		$expected = '<i class="myicon myicon-signin myicon-muted myicon-5x"></i>';
		$this->assertEquals($expected, $result);
	}

	/**
	 * @return void
	 */
	public function testYesNo() {
		$result = $this->Format->yesNo(true);
		$expected = '<i class="icon icon-yes fa fa-check" title="' . __d('tools', 'Yes') . '" data-placement="bottom" data-toggle="tooltip"></i>';
		$this->assertEquals($expected, $result);

		$result = $this->Format->yesNo(false);
		$expected = '<i class="icon icon-no fa fa-times" title="' . __d('tools', 'No') . '" data-placement="bottom" data-toggle="tooltip"></i>';
		$this->assertEquals($expected, $result);

		$result = $this->Format->yesNo('2', ['on' => 2, 'onTitle' => 'foo']);
		$expected = '<i class="icon icon-yes fa fa-check" title="foo" data-placement="bottom" data-toggle="tooltip"></i>';
		$this->assertTextContains($expected, $result);

		$result = $this->Format->yesNo('3', ['on' => 4, 'offTitle' => 'nope']);
		$expected = '<i class="icon icon-no fa fa-times" title="nope" data-placement="bottom" data-toggle="tooltip"></i>';
		$this->assertEquals($expected, $result);
	}

	/**
	 * @return void
	 */
	public function testOk() {
		$content = 'xyz';
		$data = [
			true => '<span class="ok-yes" style="color:green">xyz 1</span>',
			false => '<span class="ok-no" style="color:red">xyz 0</span>',
		];
		foreach ($data as $value => $expected) {
			$result = $this->Format->ok($content . ' ' . (int)$value, $value);
			$this->assertEquals($expected, $result);
		}
	}

	/**
	 * FormatHelperTest::testThumbs()
	 *
	 * @return void
	 */
	public function testThumbs() {
		$result = $this->Format->thumbs(1);
		$expected = '<i class="icon icon-pro fa fa-thumbs-up" title="Pro" data-placement="bottom" data-toggle="tooltip"></i>';
		$this->assertEquals($expected, $result);

		$result = $this->Format->thumbs(0);
		$expected = '<i class="icon icon-contra fa fa-thumbs-down" title="Contra" data-placement="bottom" data-toggle="tooltip"></i>';
		$this->assertEquals($expected, $result);
	}

	/**
	 * FormatHelperTest::testGenderIcon()
	 *
	 * @return void
	 */
	public function testGenderIcon() {
		$result = $this->Format->genderIcon(0);

		$expected = '<i class="icon icon-genderless fa fa-genderless" title="Unknown" data-placement="bottom" data-toggle="tooltip"></i>';
		$this->assertEquals($expected, $result);

		$result = $this->Format->genderIcon(1);

		$expected = '<i class="icon icon-male fa fa-mars" title="Male" data-placement="bottom" data-toggle="tooltip"></i>';
		$this->assertEquals($expected, $result);

		$result = $this->Format->genderIcon(2);

		$expected = '<i class="icon icon-female fa fa-venus" title="Female" data-placement="bottom" data-toggle="tooltip"></i>';
		$this->assertEquals($expected, $result);
	}

	/**
	 * FormatHelperTest::testPad()
	 *
	 * @return void
	 */
	public function testPad() {
		$result = $this->Format->pad('foo bär', 20, '-');
		$expected = 'foo bär-------------';
		$this->assertEquals($expected, $result);

		$result = $this->Format->pad('foo bär', 20, '-', STR_PAD_LEFT);
		$expected = '-------------foo bär';
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
			'limit' => 10,
		];
		$result = $this->Format->absolutePaginateCount($paginator, 2);
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
		$this->assertStringContainsString($expected, $result);
	}

	/**
	 * @return void
	 */
	public function testSlug() {
		$result = $this->Format->slug('A Baz D & Foo');
		$this->assertSame('A-Baz-D-Foo', $result);

		$this->Format->setConfig('slugger', [Text::class, 'slug']);
		$result = $this->Format->slug('A Baz D & Foo');
		$this->assertSame('A-Baz-D-Foo', $result);
	}

	/**
	 * @return void
	 */
	public function testSlugCustomObject() {
		$this->Format->setConfig('slugger', [$this, '_testSlugger']);
		$result = $this->Format->slug('A Baz D & Foo');
		$this->assertSame('a baz d & foo', $result);
	}

	/**
	 * @param string $name
	 *
	 * @return string
	 */
	public function _testSlugger($name) {
		return mb_strtolower($name);
	}

	/**
	 * @return void
	 */
	public function testNeighbors() {
		$this->skipIf(true, '//TODO');

		$neighbors = [
			'prev' => ['id' => 1, 'foo' => 'bar'],
			'next' => ['id' => 2, 'foo' => 'y'],
		];

		$result = $this->Format->neighbors($neighbors, 'foo');
		$expected = '<div class="next-prev-navi"><a href="/index/1" title="bar"><i class="icon icon-prev fa fa-prev prev" title="" data-placement="bottom" data-toggle="tooltip"></i>&nbsp;prevRecord</a>&nbsp;&nbsp;<a href="/index/2" title="y"><i class="icon icon-next fa fa-next next" title="" data-placement="bottom" data-toggle="tooltip"></i>&nbsp;nextRecord</a></div>';
		$this->assertEquals($expected, $result);
	}

	/**
	 * Test slug generation works with new slugger.
	 *
	 * @return void
	 */
	public function testSlugGenerationWithNewSlugger() {
		$neighbors = [
			'prev' => ['id' => 1, 'foo' => 'My Foo'],
			'next' => ['id' => 2, 'foo' => 'My FooBaz'],
		];

		// Only needed for fake requests (tests)
		$url = ['controller' => 'MyController', 'action' => 'myAction'];
		$result = $this->Format->neighbors($neighbors, 'foo', ['slug' => true, 'url' => $url]);

		$expected = '<div class="next-prev-navi nextPrevNavi"><a href="/my-controller/my-action/1/My-Foo" title="My Foo"><i class="icon icon-prev fa fa-prev" title="Prev" data-placement="bottom" data-toggle="tooltip"></i>&nbsp;prevRecord</a>&nbsp;&nbsp;<a href="/my-controller/my-action/2/My-FooBaz" title="My FooBaz"><i class="icon icon-next fa fa-next" title="Next" data-placement="bottom" data-toggle="tooltip"></i>&nbsp;nextRecord</a></div>';
		$this->assertEquals($expected, $result);
	}

	/**
	 * @return void
	 */
	public function testTab2space() {
		$text = "foo\t\tfoobar\tbla\n";
		$text .= "fooo\t\tbar\t\tbla\n";
		$text .= "foooo\t\tbar\t\tbla\n";
		$result = $this->Format->tab2space($text);

		$expected = <<<TXT
foo          foobar        bla
fooo         bar           bla
foooo        bar           bla

TXT;
		$this->assertTextEquals($expected, $result);
		$this->assertTrue(strpos($result, "\t") === false);
	}

	/**
	 * @return void
	 */
	public function testArray2table() {
		$array = [
			['x' => '0', 'y' => '0.5', 'z' => '0.9'],
			['1', '2', '3'],
			['4', '5', '6'],
		];

		$is = $this->Format->array2table($array);
		$this->assertTextContains('<table class="table">', $is);
		$this->assertTextContains('</table>', $is);
		$this->assertTextContains('<th>', $is);

		// recursive
		$array = [
			['a' => ['2'], 'b' => ['2'], 'c' => ['2']],
			[['2'], ['2'], ['2']],
			[['2'], ['2'], [['s' => '3', 't' => '4']]],
		];

		$is = $this->Format->array2table($array, ['recursive' => true]);
		$expected = <<<TEXT
<table class="table">
	<tr><th>a</th><th>b</th><th>c</th></tr>
	<tr><td>
<table class="table">
	<tr><th>0</th></tr>
	<tr><td>2</td></tr>
</table>
</td><td>
<table class="table">
	<tr><th>0</th></tr>
	<tr><td>2</td></tr>
</table>
</td><td>
<table class="table">
	<tr><th>0</th></tr>
	<tr><td>2</td></tr>
</table>
</td></tr>
	<tr><td>
<table class="table">
	<tr><th>0</th></tr>
	<tr><td>2</td></tr>
</table>
</td><td>
<table class="table">
	<tr><th>0</th></tr>
	<tr><td>2</td></tr>
</table>
</td><td>
<table class="table">
	<tr><th>0</th></tr>
	<tr><td>2</td></tr>
</table>
</td></tr>
	<tr><td>
<table class="table">
	<tr><th>0</th></tr>
	<tr><td>2</td></tr>
</table>
</td><td>
<table class="table">
	<tr><th>0</th></tr>
	<tr><td>2</td></tr>
</table>
</td><td>
<table class="table">
	<tr><th>s</th><th>t</th></tr>
	<tr><td>3</td><td>4</td></tr>
</table>
</td></tr>
</table>
TEXT;
		$this->assertTextEquals($expected, $is);

		$array = [
			['x' => '0', 'y' => '0.5', 'z' => '0.9'],
			['1', '2', '3'],
		];

		$options = [
			'heading' => false,
		];
		$attributes = [
			'class' => 'foo',
			'data-x' => 'y',
		];

		$is = $this->Format->array2table($array, $options, $attributes);
		$this->assertTextContains('<table class="foo" data-x="y">', $is);
		$this->assertTextContains('</table>', $is);
		$this->assertTextNotContains('<th>', $is);
	}

	/**
	 * @return void
	 */
	public function tearDown(): void {
		parent::tearDown();

		unset($this->Format);
	}

}
