<?php

if (!defined('CAKEPHP_UNIT_TEST_EXECUTION')) {
	define('CAKEPHP_UNIT_TEST_EXECUTION', 1);
}

define('VALID_TEST_EMAIL', 'graham@grahamweldon.com'); # for testing normal behavior
define('GARBAGE_TEST_EMAIL', 'test@test.de'); # for testing default image behavior

App::uses('HtmlHelper', 'View/Helper');
App::uses('GravatarHelper', 'Tools.View/Helper');
App::uses('MyCakeTestCase', 'Tools.TestSuite');
App::uses('View', 'View');

/**
 * Gravatar Test Case
 *
 */
class GravatarHelperTest extends MyCakeTestCase {

	/**
	 * SetUp method
	 */
	public function setUp() {
		parent::setUp();

		$this->Gravatar = new GravatarHelper(new View(null));
		$this->Gravatar->Html = new HtmlHelper(new View(null));
	}

	/**
	 * TearDown method
	 */
	public function tearDown() {
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
			$this->out($image . ' ');
		}
		$this->assertTrue(is_array($is) && (count($is) === $expectedCount));
	}

	/**
	 * @return void
	 */
	public function testImages() {
		$is = $this->Gravatar->image(GARBAGE_TEST_EMAIL);
		$this->out($is);
		$this->assertTrue(!empty($is));

		$is = $this->Gravatar->image(Configure::read('Config.adminEmail'));
		$this->out($is);
		$this->assertTrue(!empty($is));

		$is = $this->Gravatar->image(VALID_TEST_EMAIL);
		$this->out($is);
		$this->assertTrue(!empty($is));

		$is = $this->Gravatar->image(VALID_TEST_EMAIL, array('size' => '200'));
		$this->out($is);
		$this->assertTrue(!empty($is));

		$is = $this->Gravatar->image(VALID_TEST_EMAIL, array('size' => '20'));
		$this->out($is);
		$this->assertTrue(!empty($is));

		$is = $this->Gravatar->image(VALID_TEST_EMAIL, array('rating' => 'X')); # note the capit. x
		$this->out($is);
		$this->assertTrue(!empty($is));

		$is = $this->Gravatar->image(VALID_TEST_EMAIL, array('ext' => true));
		$this->out($is);
		$this->assertTrue(!empty($is));

		$is = $this->Gravatar->image(VALID_TEST_EMAIL, array('default' => 'none'));
		$this->out($is);
		$this->assertTrue(!empty($is));

		$is = $this->Gravatar->image(GARBAGE_TEST_EMAIL, array('default' => 'none'));
		$this->out($is);
		$this->assertTrue(!empty($is));

		$is = $this->Gravatar->image(GARBAGE_TEST_EMAIL, array('default' => 'http://2.gravatar.com/avatar/8379aabc84ecee06f48d8ca48e09eef4?d=identicon'));
		$this->out($is);
		$this->assertTrue(!empty($is));
	}

/** BASE TEST CASES **/

	/**
	 * TestBaseUrlGeneration
	 *
	 * @return void
	 */
	public function testBaseUrlGeneration() {
		$expected = 'http://www.gravatar.com/avatar/' . md5('example@gravatar.com');
		$result = $this->Gravatar->imageUrl('example@gravatar.com', array('ext' => false, 'default' => 'wavatar'));
		list($url, $params) = explode('?', $result);
		$this->assertEquals($expected, $url);
	}

	/**
	 * TestExtensions
	 *
	 * @return void
	 */
	public function testExtensions() {
		$result = $this->Gravatar->imageUrl('example@gravatar.com', array('ext' => true, 'default' => 'wavatar'));
		$this->assertRegExp('/\.jpg(?:$|\?)/', $result);
	}

	/**
	 * TestRating
	 *
	 * @return void
	 */
	public function testRating() {
		$result = $this->Gravatar->imageUrl('example@gravatar.com', array('ext' => true, 'default' => 'wavatar'));
		$this->assertRegExp('/\.jpg(?:$|\?)/', $result);
	}

	/**
	 * TestAlternateDefaultIcon
	 *
	 * @return void
	 */
	public function testAlternateDefaultIcon() {
		$result = $this->Gravatar->imageUrl('example@gravatar.com', array('ext' => false, 'default' => 'wavatar'));
		list($url, $params) = explode('?', $result);
		$this->assertRegExp('/default=wavatar/', $params);
	}

	/**
	 * TestAlternateDefaultIconCorrection
	 *
	 * @return void
	 */
	public function testAlternateDefaultIconCorrection() {
		$result = $this->Gravatar->imageUrl('example@gravatar.com', array('ext' => false, 'default' => '12345'));
		$this->assertRegExp('/[^\?]+/', $result);
	}

	/**
	 * TestSize
	 *
	 * @return void
	 */
	public function testSize() {
		$result = $this->Gravatar->imageUrl('example@gravatar.com', array('size' => '120'));
		list($url, $params) = explode('?', $result);
		$this->assertRegExp('/size=120/', $params);
	}

	/**
	 * TestImageTag
	 *
	 * @return void
	 */
	public function testImageTag() {
		$expected = '<img src="http://www.gravatar.com/avatar/' . md5('example@gravatar.com') . '" alt="" />';
		$result = $this->Gravatar->image('example@gravatar.com', array('ext' => false));
		$this->assertEquals($expected, $result);

		$expected = '<img src="http://www.gravatar.com/avatar/' . md5('example@gravatar.com') . '" alt="Gravatar" />';
		$result = $this->Gravatar->image('example@gravatar.com', array('ext' => false, 'alt' => 'Gravatar'));
		$this->assertEquals($expected, $result);
	}

	/**
	 * TestDefaulting
	 *
	 * @return void
	 */
	public function testDefaulting() {
		$result = $this->Gravatar->imageUrl('example@gravatar.com', array('default' => 'wavatar', 'size' => 'default'));
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
		$result = $this->Gravatar->imageUrl('example@gravatar.com', array('ext' => false));
		$this->assertEquals($expected, $result);

		$expected = 'http://www.gravatar.com/avatar/' . md5('example@gravatar.com');
		$result = $this->Gravatar->imageUrl('example@gravatar.com', array('ext' => false, 'secure' => false));
		$this->assertEquals($expected, $result);

		$_SERVER['HTTPS'] = true;
		$expected = 'http://www.gravatar.com/avatar/' . md5('example@gravatar.com');
		$result = $this->Gravatar->imageUrl('example@gravatar.com', array('ext' => false, 'secure' => false));
		$this->assertEquals($expected, $result);
	}

	/**
	 * TestSecureUrl
	 *
	 * @return void
	 */
	public function testSecureUrl() {
		$expected = 'https://secure.gravatar.com/avatar/' . md5('example@gravatar.com');
		$result = $this->Gravatar->imageUrl('example@gravatar.com', array('ext' => false, 'secure' => true));
		$this->assertEquals($expected, $result);

		$_SERVER['HTTPS'] = true;

		$this->Gravatar = new GravatarHelper(new View(null));
		$this->Gravatar->Html = new HtmlHelper(new View(null));

		$expected = 'https://secure.gravatar.com/avatar/' . md5('example@gravatar.com');
		$result = $this->Gravatar->imageUrl('example@gravatar.com', array('ext' => false));
		$this->assertEquals($expected, $result);

		$expected = 'https://secure.gravatar.com/avatar/' . md5('example@gravatar.com');
		$result = $this->Gravatar->imageUrl('example@gravatar.com', array('ext' => false, 'secure' => true));
		$this->assertEquals($expected, $result);
	}

}
