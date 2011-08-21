<?php

if (!defined('CAKEPHP_UNIT_TEST_EXECUTION')) {
	define('CAKEPHP_UNIT_TEST_EXECUTION', 1);

}

define('VALID_TEST_EMAIL', 'graham@grahamweldon.com'); # for testing normal behavior
define('GARBIGE_TEST_EMAIL', 'test@test.de'); # for testing default image behavior

App::import('Helper', 'Html');
App::import('Helper', 'Tools.Gravatar');

/**
 * Gravatar Test Case
 *
 * 2010-05-27 ms
 */
class GravatarTest extends CakeTestCase {

	/**
	 * setUp method
	 */
	function setUp() {
		$this->Gravatar = new GravatarHelper();
		$this->Gravatar->Html = new HtmlHelper();
	}

	/**
	 * tearDown method
	 */
	function tearDown() {
		unset($this->Gravatar);
	}

/** OWN ONES **/

	/**
	 * @access public
	 * @return void
	 * 2009-07-30 ms
	 */
	function testDefaultImages() {

		$is = $this->Gravatar->defaultImages();
		$expectedCount = 7;

		foreach ($is as $image) {
			echo $image.' ';
		}
		$this->assertTrue(is_array($is) && (count($is) === $expectedCount));

	}


	/**
	 * @access public
	 * @return void
	 * 2009-07-30 ms
	 */
	function testImages() {

		$is = $this->Gravatar->image(GARBIGE_TEST_EMAIL);
		echo $is;
		$this->assertTrue(!empty($is));

		$is = $this->Gravatar->image(Configure::read('Config.admin_email'));
		echo $is;
		$this->assertTrue(!empty($is));

		$is = $this->Gravatar->image(VALID_TEST_EMAIL);
		echo $is;
		$this->assertTrue(!empty($is));

		$is = $this->Gravatar->image(VALID_TEST_EMAIL, array('size'=>'200'));
		echo $is;
		$this->assertTrue(!empty($is));

		$is = $this->Gravatar->image(VALID_TEST_EMAIL, array('size'=>'20'));
		echo $is;
		$this->assertTrue(!empty($is));

		$is = $this->Gravatar->image(VALID_TEST_EMAIL, array('rating'=>'X')); # note the capit. x
		echo $is;
		$this->assertTrue(!empty($is));

		$is = $this->Gravatar->image(VALID_TEST_EMAIL, array('ext'=>true));
		echo $is;
		$this->assertTrue(!empty($is));

		$is = $this->Gravatar->image(VALID_TEST_EMAIL, array('default'=>'none'));
		echo $is;
		$this->assertTrue(!empty($is));

		$is = $this->Gravatar->image(GARBIGE_TEST_EMAIL, array('default'=>'none'));
		echo $is;
		$this->assertTrue(!empty($is));

		$is = $this->Gravatar->image(GARBIGE_TEST_EMAIL, array('default'=>'http://2.gravatar.com/avatar/8379aabc84ecee06f48d8ca48e09eef4?d=identicon'));
		echo $is;
		$this->assertTrue(!empty($is));

	}

/** BASE TEST CASES **/

/**
 * testBaseUrlGeneration
 *
 * @return void
 * @access public
 */
	public function testBaseUrlGeneration() {
		$expected = 'http://www.gravatar.com/avatar/' . md5('example@gravatar.com');
		$result = $this->Gravatar->url('example@gravatar.com', array('ext' => false, 'default' => 'wavatar'));
		list($url, $params) = explode('?', $result);
		$this->assertEqual($expected, $url);
	}

/**
 * testExtensions
 *
 * @return void
 * @access public
 */
	public function testExtensions() {
		$result = $this->Gravatar->url('example@gravatar.com', array('ext' => true, 'default' => 'wavatar'));
		$this->assertPattern('/\.jpg(?:$|\?)/', $result);
	}

/**
 * testRating
 *
 * @return void
 * @access public
 */
	public function testRating() {
		$result = $this->Gravatar->url('example@gravatar.com', array('ext' => true, 'default' => 'wavatar'));
		$this->assertPattern('/\.jpg(?:$|\?)/', $result);
	}

/**
 * testAlternateDefaultIcon
 *
 * @return void
 * @access public
 */
	public function testAlternateDefaultIcon() {
		$result = $this->Gravatar->url('example@gravatar.com', array('ext' => false, 'default' => 'wavatar'));
		list($url, $params) = explode('?', $result);
		$this->assertPattern('/default=wavatar/', $params);
	}

/**
 * testAlternateDefaultIconCorrection
 *
 * @return void
 * @access public
 */
	public function testAlternateDefaultIconCorrection() {
		$result = $this->Gravatar->url('example@gravatar.com', array('ext' => false, 'default' => '12345'));
		$this->assertPattern('/[^\?]+/', $result);
	}

/**
 * testSize
 *
 * @return void
 * @access public
 */
	public function testSize() {
		$result = $this->Gravatar->url('example@gravatar.com', array('size' => '120'));
		list($url, $params) = explode('?', $result);
		$this->assertPattern('/size=120/', $params);
	}

/**
 * testImageTag
 *
 * @return void
 * @access public
 */
	public function testImageTag() {
		$expected = '<img src="http://www.gravatar.com/avatar/' . md5('example@gravatar.com') . '" alt="" />';
		$result = $this->Gravatar->image('example@gravatar.com', array('ext' => false));
		$this->assertEqual($expected, $result);

		$expected = '<img src="http://www.gravatar.com/avatar/' . md5('example@gravatar.com') . '" alt="Gravatar" />';
		$result = $this->Gravatar->image('example@gravatar.com', array('ext' => false, 'alt' => 'Gravatar'));
		$this->assertEqual($expected, $result);
	}

/**
 * testDefaulting
 *
 * @return void
 * @access public
 */
	public function testDefaulting() {
		$result = $this->Gravatar->url('example@gravatar.com', array('default' => 'wavatar', 'size' => 'default'));
		list($url, $params) = explode('?', $result);
		$this->assertEqual($params, 'default=wavatar');
	}

/**
 * testNonSecureUrl
 *
 * @return void
 * @access public
 */
	public function testNonSecureUrl() {
		$_SERVER['HTTPS'] = false;

		$expected = 'http://www.gravatar.com/avatar/' . md5('example@gravatar.com');
		$result = $this->Gravatar->url('example@gravatar.com', array('ext' => false));
		$this->assertEqual($expected, $result);

		$expected = 'http://www.gravatar.com/avatar/' . md5('example@gravatar.com');
		$result = $this->Gravatar->url('example@gravatar.com', array('ext' => false, 'secure' => false));
		$this->assertEqual($expected, $result);

		$_SERVER['HTTPS'] = true;
		$expected = 'http://www.gravatar.com/avatar/' . md5('example@gravatar.com');
		$result = $this->Gravatar->url('example@gravatar.com', array('ext' => false, 'secure' => false));
		$this->assertEqual($expected, $result);
	}

/**
 * testSecureUrl
 *
 * @return void
 * @access public
 */
	public function testSecureUrl() {
		$expected = 'https://secure.gravatar.com/avatar/' . md5('example@gravatar.com');
		$result = $this->Gravatar->url('example@gravatar.com', array('ext' => false, 'secure' => true));
		$this->assertEqual($expected, $result);

		$_SERVER['HTTPS'] = true;

		$expected = 'https://secure.gravatar.com/avatar/' . md5('example@gravatar.com');
		$result = $this->Gravatar->url('example@gravatar.com', array('ext' => false));
		$this->assertEqual($expected, $result);

		$expected = 'https://secure.gravatar.com/avatar/' . md5('example@gravatar.com');
		$result = $this->Gravatar->url('example@gravatar.com', array('ext' => false, 'secure' => true));
		$this->assertEqual($expected, $result);
	}


}

