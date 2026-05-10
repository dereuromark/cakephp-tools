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
	 * @var string
	 */
	protected $testEmail;

	/**
	 * @var string
	 */
	protected $garageEmail;

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
		$expected = 'https://www.gravatar.com/avatar/' . hash('sha256', 'example@gravatar.com');
		$result = $this->Gravatar->url('example@gravatar.com', ['ext' => false, 'default' => 'wavatar']);
		[$url, $params] = explode('?', $result);
		$this->assertSame($expected, $url);
	}

	/**
	 * Hash uses SHA-256 (Gravatar's modern identifier), normalizes via trim + lowercase.
	 *
	 * @return void
	 */
	public function testHashIsSha256AndCaseInsensitive() {
		$result1 = $this->Gravatar->url('Example@gravatar.com');
		$result2 = $this->Gravatar->url('  example@gravatar.com  ');
		$expectedHash = hash('sha256', 'example@gravatar.com');

		$this->assertStringContainsString($expectedHash, $result1);
		$this->assertStringContainsString($expectedHash, $result2);
		// And NOT MD5.
		$this->assertStringNotContainsString(md5('example@gravatar.com'), $result1);
	}

	/**
	 * Mixed content is blocked by every modern browser, so the helper must always emit
	 * an HTTPS URL even when secure=false is passed (the option is preserved as a no-op
	 * for backwards compatibility).
	 *
	 * @return void
	 */
	public function testAlwaysEmitsHttpsRegardlessOfSecureOption() {
		$_SERVER['HTTPS'] = false;
		$this->Gravatar = new GravatarHelper(new View(null));

		foreach ([false, true] as $secure) {
			$url = $this->Gravatar->url('example@gravatar.com', ['ext' => false, 'secure' => $secure]);
			$this->assertStringStartsWith('https://www.gravatar.com/avatar/', $url, 'secure=' . var_export($secure, true));
			$this->assertStringNotContainsString('http://', $url);
			$this->assertStringNotContainsString('secure.gravatar.com', $url);
		}
	}

	/**
	 * TestExtensions
	 *
	 * @return void
	 */
	public function testExtensions() {
		$result = $this->Gravatar->url('example@gravatar.com', ['ext' => true, 'default' => 'wavatar']);
		$this->assertMatchesRegularExpression('/\.jpg(?:$|\?)/', $result);
	}

	/**
	 * TestRating
	 *
	 * @return void
	 */
	public function testRating() {
		$result = $this->Gravatar->url('example@gravatar.com', ['ext' => true, 'default' => 'wavatar']);
		$this->assertMatchesRegularExpression('/\.jpg(?:$|\?)/', $result);
	}

	/**
	 * TestAlternateDefaultIcon
	 *
	 * @return void
	 */
	public function testAlternateDefaultIcon() {
		$result = $this->Gravatar->url('example@gravatar.com', ['ext' => false, 'default' => 'wavatar']);
		[$url, $params] = explode('?', $result);
		$this->assertMatchesRegularExpression('/default=wavatar/', $params);
	}

	/**
	 * TestAlternateDefaultIconCorrection
	 *
	 * @return void
	 */
	public function testAlternateDefaultIconCorrection() {
		$result = $this->Gravatar->url('example@gravatar.com', ['ext' => false, 'default' => '12345']);
		$this->assertMatchesRegularExpression('/[^\?]+/', $result);
	}

	/**
	 * TestSize
	 *
	 * @return void
	 */
	public function testSize() {
		$result = $this->Gravatar->url('example@gravatar.com', ['size' => '120']);
		[$url, $params] = explode('?', $result);
		$this->assertMatchesRegularExpression('/size=120/', $params);
	}

	/**
	 * TestImageTag
	 *
	 * @return void
	 */
	public function testImageTag() {
		$hash = hash('sha256', 'example@gravatar.com');

		$expected = '<img src="https://www.gravatar.com/avatar/' . $hash . '" alt="">';
		$result = $this->Gravatar->image('example@gravatar.com', ['ext' => false]);
		$this->assertSame($expected, $result);

		$expected = '<img src="https://www.gravatar.com/avatar/' . $hash . '" alt="Gravatar">';
		$result = $this->Gravatar->image('example@gravatar.com', ['ext' => false, 'alt' => 'Gravatar']);
		$this->assertSame($expected, $result);
	}

	/**
	 * TestDefaulting
	 *
	 * @return void
	 */
	public function testDefaulting() {
		$result = $this->Gravatar->url('example@gravatar.com', ['default' => 'wavatar', 'size' => 'default']);
		[$url, $params] = explode('?', $result);
		$this->assertEquals($params, 'default=wavatar');
	}

	/**
	 * TestNonSecureUrl
	 *
	 * @return void
	 */
	public function testNonSecureUrl() {
		// `secure => false` is preserved as a no-op for BC; the URL must still be HTTPS
		// to avoid mixed-content blocking from a HTTPS page.
		$expected = 'https://www.gravatar.com/avatar/' . hash('sha256', 'example@gravatar.com');

		$_SERVER['HTTPS'] = false;
		$this->assertSame($expected, $this->Gravatar->url('example@gravatar.com', ['ext' => false]));
		$this->assertSame($expected, $this->Gravatar->url('example@gravatar.com', ['ext' => false, 'secure' => false]));

		$_SERVER['HTTPS'] = true;
		$this->assertSame($expected, $this->Gravatar->url('example@gravatar.com', ['ext' => false, 'secure' => false]));
	}

	/**
	 * TestSecureUrl
	 *
	 * @return void
	 */
	public function testSecureUrl() {
		$expected = 'https://www.gravatar.com/avatar/' . hash('sha256', 'example@gravatar.com');

		$this->assertSame($expected, $this->Gravatar->url('example@gravatar.com', ['ext' => false, 'secure' => true]));

		$_SERVER['HTTPS'] = true;
		$this->Gravatar = new GravatarHelper(new View(null));

		$this->assertSame($expected, $this->Gravatar->url('example@gravatar.com', ['ext' => false]));
		$this->assertSame($expected, $this->Gravatar->url('example@gravatar.com', ['ext' => false, 'secure' => true]));
	}

}
