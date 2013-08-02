<?php
/**
 * WhoDidIt Model Behavior Test Cases
 *
 * PHP 5
 *
 * @author Mark Scherrer
 * @author Marc Würth
 * @version 1.3
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 * @link https://github.com/dereuromark/tools
 **/

App::import('Behavior', 'Tools.WhoDidIt');
App::uses('AppModel', 'Model');
App::uses('MyCakeTestCase', 'Tools.TestSuite');

/**)
 * WhoDidIt Model Behavior Test Cases
 */
class WhoDidItBehaviorTest extends MyCakeTestCase {

	/**
	 * Model for tests
	 *
	 * @var
	 */
	public $Model;

	/**
	 * Fixtures for tests
	 *
	 * @var array
	 */
	public $fixtures = array('plugin.tools.who_did_it_player');

	/**
	 * Setup for tests
	 */
	public function setUp() {
		parent::setUp();

		$this->Model = ClassRegistry::init('Article');
		$this->Model->Behaviors->load('Tools.WhoDidIt');
	}

	/**
	 * Test after creation of a record
	 *
	 * Should fill created_by and modified_by fields by default.
	 */
	public function testAfterCreated() {
		// Stop here and mark this test as incomplete.
		$this->markTestIncomplete(
			'This test has not been implemented yet.'
		);
	}

	/**
	 * Test after updating a record
	 *
	 * Should fill modified_by field by default unless it appears in the data array.
	 */
	public function testAfterModified() {
		// Stop here and mark this test as incomplete.
		$this->markTestIncomplete(
			'This test has not been implemented yet.'
		);
	}

	/**
	 * Test auto binding to user model
	 */
	public function testAutoBind() {
		// Stop here and mark this test as incomplete.
		$this->markTestIncomplete(
			'This test has not been implemented yet.'
		);
	}

	/**
	 * Test is instance of WhoDidItBehavior
	 */
	public function testInstanceOf() {
		$this->assertInstanceOf('WhoDidItBehavior', $this->Model->Behaviors->WhoDidIt);
	}

	/**
	 * Test different key set in auth_session
	 */
	public function testAuthSessionKey() {
		// Stop here and mark this test as incomplete.
		$this->markTestIncomplete(
			'This test has not been implemented yet.'
		);
	}

	/**
	 * Test different user model set in user_model
	 */
	public function testDifferentUserModel() {
		// Stop here and mark this test as incomplete.
		$this->markTestIncomplete(
			'This test has not been implemented yet.'
		);
	}

	/**
	 * Test different field set in created_by_field
	 */
	public function testDifferentCreatedByField() {
		// Stop here and mark this test as incomplete.
		$this->markTestIncomplete(
			'This test has not been implemented yet.'
		);
	}

	/**
	 * Test different field set in modified_by_field
	 */
	public function testDifferentModifiedByField() {
		// Stop here and mark this test as incomplete.
		$this->markTestIncomplete(
			'This test has not been implemented yet.'
		);
	}

	/**
	 * Test different field set in confirmed_by_field
	 */
	public function testDifferentConfirmedByField() {
		// Stop here and mark this test as incomplete.
		$this->markTestIncomplete(
			'This test has not been implemented yet.'
		);
	}

	/**
	 * Test auto_bind set to false
	 */
	public function testDisabledAutoBind() {
		// Stop here and mark this test as incomplete.
		$this->markTestIncomplete(
			'This test has not been implemented yet.'
		);
	}

	/**
	 * Test after Update with force_modified set to true
	 *
	 * This should force fill the created_by field
	 * even when the field appears in the data array.
	 * Also ignores the content of the data array.
	 */
	public function testAfterUpdateWithEnabledForceModified() {
		// Stop here and mark this test as incomplete.
		$this->markTestIncomplete(
			'This test has not been implemented yet.'
		);
	}

}