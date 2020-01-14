<?php

namespace Tools\Test\TestCase\View\Helper;

use Cake\View\View;
use Shim\TestSuite\TestCase;
use Tools\View\Helper\GravatarHelper;

/**
 * Gravatar Test Case
 */
class GravatarHelperTest extends TestCase {

	/**
	 * @var \Tools\View\Helper\GravatarHelper
	 */
	protected $Gravatar;

	/**
	 * SetUp method
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		$this->testEmail = 'graham@grahamweldon.com'; // For testing normal behavior
		$this->garageEmail = 'test@test.de'; // For testing default image behavior

		$this->Gravatar = new GravatarHelper(new View(null));
	}

	/**
	 * TearDown method
	 *
	 * @return void
	 */
	public function tearDown(): void {
		parent::tearDown();

		unset($this->Gravatar);
	}

	/**
	 * @return void
	 */
	public function testDefaultImages() {
		$is = $this->Gravatar->defaultImages();
		$expectedCount = 7;

		foreach ($is as $image) {
			//$this->debug($image . ' ');
		}
		$this->assertTrue(is_array($is) && (count($is) === $expectedCount));
	}

	/**
	 * @return void
	 */
	public function testImage() {
		$is = $this->Gravatar->image($this->garageEmail);

		$this->assertTrue(!empty($is));

		$is = $this->Gravatar->image($this->testEmail);
		$this->assertTextContains('.gravatar.com/avatar/', $is);

		$is = $this->Gravatar->image($this->testEmail, ['size' => '200']);
		$this->assertTextContains('?size=200"', $is);

		$is = $this->Gravatar->image($this->testEmail, ['size' => '20']);
		$this->assertTextContains('?size=20"', $is);

		$is = $this->Gravatar->image($this->testEmail, ['rating' => 'X']); # note the capit. x
		$this->assertTextContains('?rating=x"', $is);

		$is = $this->Gravatar->image($this->testEmail, ['ext' => true]);
		$this->assertTextContains('.jpg"', $is);

		$is = $this->Gravatar->image($this->testEmail, ['default' => 'none']);
		$this->assertTrue(!empty($is));

		$is = $this->Gravatar->image($this->garageEmail, ['default' => 'none']);
		$this->assertTrue(!empty($is));

		$is = $this->Gravatar->image($this->garageEmail, ['default' => 'http://2.gravatar.com/avatar/8379aabc84ecee06f48d8ca48e09eef4?d=identicon']);
		$this->assertTrue(!empty($is));

		$is = $this->Gravatar->image($this->testEmail, ['size' => '20']);
		$this->assertTextContains('?size=20"', $is);

		$is = $this->Gravatar->image($this->testEmail, ['rating' => 'X', 'size' => 20, 'default' => 'none']);
		$this->assertTextContains('?rating=x&amp;size=20&amp;default=none"', $is);
	}

	/**
	 * TestBaseUrlGeneration
	 *
	 * @return void
	 */
	public function testBaseUrlGeneration() {
		$expected = 'http://www.gravatar.com/avatar/' . md5('example@gravatar.com');
		$result = $this->Gravatar->url('example@gravatar.com', ['ext' => false, 'default' => 'wavatar']);
		list($url, $params) = explode('?', $result);
		$this->assertEquals($expected, $url);
	}

	/**
	 * TestExtensions
	 *
	 * @return void
	 */
	public function testExtensions() {
		$result = $this->Gravatar->url('example@gravatar.com', ['ext' => true, 'default' => 'wavatar']);
		$this->assertRegExp('/\.jpg(?:$|\?)/', $result);
	}

	/**
	 * TestRating
	 *
	 * @return void
	 */
	public function testRating() {
		$result = $this->Gravatar->url('example@gravatar.com', ['ext' => true, 'default' => 'wavatar']);
		$this->assertRegExp('/\.jpg(?:$|\?)/', $result);
	}

	/**
	 * TestAlternateDefaultIcon
	 *
	 * @return void
	 */
	public function testAlternateDefaultIcon() {
		$result = $this->Gravatar->url('example@gravatar.com', ['ext' => false, 'default' => 'wavatar']);
		list($url, $params) = explode('?', $result);
		$this->assertRegExp('/default=wavatar/', $params);
	}

	/**
	 * TestAlternateDefaultIconCorrection
	 *
	 * @return void
	 */
	public function testAlternateDefaultIconCorrection() {
		$result = $this->Gravatar->url('example@gravatar.com', ['ext' => false, 'default' => '12345']);
		$this->assertRegExp('/[^\?]+/', $result);
	}

	/**
	 * TestSize
	 *
	 * @return void
	 */
	public function testSize() {
		$result = $this->Gravatar->url('example@gravatar.com', ['size' => '120']);
		list($url, $params) = explode('?', $result);
		$this->assertRegExp('/size=120/', $params);
	}

	/**
	 * TestImageTag
	 *
	 * @return void
	 */
	public function testImageTag() {
		$expected = '<img src="http://www.gravatar.com/avatar/' . md5('example@gravatar.com') . '" alt=""/>';
		$result = $this->Gravatar->image('example@gravatar.com', ['ext' => false]);
		$this->assertEquals($expected, $result);

		$expected = '<img src="http://www.gravatar.com/avatar/' . md5('example@gravatar.com') . '" alt="Gravatar"/>';
		$result = $this->Gravatar->image('example@gravatar.com', ['ext' => false, 'alt' => 'Gravatar']);
		$this->assertEquals($expected, $result);
	}

	/**
	 * TestDefaulting
	 *
	 * @return void
	 */
	public function testDefaulting() {
		$result = $this->Gravatar->url('example@gravatar.com', ['default' => 'wavatar', 'size' => 'default']);
		list($url, $params) = explode('?', $result);
		$this->assertEquals($params, 'default=wavatar');
	}

	/**
	 * TestNonSecureUrl
	 *
	 * @return void
	 */
	public function testNonSecureUrl() {
		$_SERVER['HTTPS'] = false;

		$expected = 'http://www.gravatar.com/avatar/' . md5('example@gravatar.com');
		$result = $this->Gravatar->url('example@gravatar.com', ['ext' => false]);
		$this->assertEquals($expected, $result);

		$expected = 'http://www.gravatar.com/avatar/' . md5('example@gravatar.com');
		$result = $this->Gravatar->url('example@gravatar.com', ['ext' => false, 'secure' => false]);
		$this->assertEquals($expected, $result);

		$_SERVER['HTTPS'] = true;
		$expected = 'http://www.gravatar.com/avatar/' . md5('example@gravatar.com');
		$result = $this->Gravatar->url('example@gravatar.com', ['ext' => false, 'secure' => false]);
		$this->assertEquals($expected, $result);
	}

	/**
	 * TestSecureUrl
	 *
	 * @return void
	 */
	public function testSecureUrl() {
		$expected = 'https://secure.gravatar.com/avatar/' . md5('example@gravatar.com');
		$result = $this->Gravatar->url('example@gravatar.com', ['ext' => false, 'secure' => true]);
		$this->assertEquals($expected, $result);

		$_SERVER['HTTPS'] = true;

		$this->Gravatar = new GravatarHelper(new View(null));

		$expected = 'https://secure.gravatar.com/avatar/' . md5('example@gravatar.com');
		$result = $this->Gravatar->url('example@gravatar.com', ['ext' => false]);
		$this->assertEquals($expected, $result);

		$expected = 'https://secure.gravatar.com/avatar/' . md5('example@gravatar.com');
		$result = $this->Gravatar->url('example@gravatar.com', ['ext' => false, 'secure' => true]);
		$this->assertEquals($expected, $result);
	}

}
