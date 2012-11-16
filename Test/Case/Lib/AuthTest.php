<?php
App::uses('Auth', 'Tools.Lib');
App::uses('MyCakeTestCase', 'Tools.TestSuite');

/**
 * 2010-06-29 ms
 */
class AuthTest extends MyCakeTestCase {

	public $fixtures = array('core.session');

	public function setUp() {
		ClassRegistry::init('Session');
	}

	public function tearDown() {
		ClassRegistry::flush();
	}

	public function testHasRole() {
		$res = Auth::hasRole(1, array(2, 3, 6));
		$this->assertFalse($res);

		$res = Auth::hasRole(3, array(2, 3, 6));
		$this->assertTrue($res);

		$res = Auth::hasRole(3, 1);
		$this->assertFalse($res);

		$res = Auth::hasRole(3, '3');
		$this->assertTrue($res);

		$res = Auth::hasRole(3, '');
		$this->assertFalse($res);
	}

	public function testHasRoles() {
		$res = Auth::hasRoles(array(1, 3), true, array(2, 3, 6));
		$this->assertTrue($res);

		$res = Auth::hasRoles(array(3), true, array(2, 3, 6));
		$this->assertTrue($res);

		$res = Auth::hasRoles(array(), true, array(2, 3, 6));
		$this->assertFalse($res);

		$res = Auth::hasRoles(null, true, array(2, 3, 6));
		$this->assertFalse($res);

		$res = Auth::hasRoles(array(2, 7), false, array(2, 3, 6));
		$this->assertFalse($res);

		$res = Auth::hasRoles(array(2, 6), false, array(2, 3, 6));
		$this->assertTrue($res);

		$res = Auth::hasRoles(array(2, 6), true, array(2, 3, 6));
		$this->assertTrue($res);

		$res = Auth::hasRoles(array(9, 11), true, array());
		$this->assertFalse($res);

		$res = Auth::hasRoles(array(9, 11), true, '');
		$this->assertFalse($res);

		$res = Auth::hasRoles(array(2, 7), false, array());
		$this->assertFalse($res);

		$res = Auth::hasRoles(array(2, 7), false);
		$this->assertFalse($res);
	}




}

