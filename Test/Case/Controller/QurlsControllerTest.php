<?php
App::uses('QurlsController', 'ToolsExtra.Controller');
App::uses('MyCakeTestCase', 'Tools.TestSuite');

/**
 * QurlsController Test Case
 *
 */
class QurlsControllerTest extends MyCakeTestCase {

	public $Qurls;

	/**
	 * Fixtures
	 *
	 * @var array
	 */
	public $fixtures = ['plugin.tools.qurl', 'core.auth_user'];

	/**
	 * SetUp method
	 *
	 * @return void
	 */
	public function setUp() {
		parent::setUp();

		$this->Qurls = new QurlsController();
	}

	/**
	 * TearDown method
	 *
	 * @return void
	 */
	public function tearDown() {
		unset($this->Qurls);

		parent::tearDown();
	}

	/**
	 * TestIndex method
	 *
	 * @return void
	 */
	public function testIndex() {
	}

	/**
	 * TestView method
	 *
	 * @return void
	 */
	public function testView() {
	}

	/**
	 * TestAdd method
	 *
	 * @return void
	 */
	public function testAdd() {
	}

	/**
	 * TestEdit method
	 *
	 * @return void
	 */
	public function testEdit() {
	}

	/**
	 * TestDelete method
	 *
	 * @return void
	 */
	public function testDelete() {
	}

	/**
	 * TestAdminIndex method
	 *
	 * @return void
	 */
	public function testAdminIndex() {
	}

	/**
	 * TestAdminView method
	 *
	 * @return void
	 */
	public function testAdminView() {
	}

	/**
	 * TestAdminAdd method
	 *
	 * @return void
	 */
	public function testAdminAdd() {
	}

	/**
	 * TestAdminEdit method
	 *
	 * @return void
	 */
	public function testAdminEdit() {
	}

	/**
	 * TestAdminDelete method
	 *
	 * @return void
	 */
	public function testAdminDelete() {
	}

}

/**
 * TestQurlsController
 *
 */
class TestQurlsController extends QurlsController {

	/**
	 * Auto render
	 *
	 * @var bool
	 */
	public $autoRender = false;

	/**
	 * Redirect action
	 *
	 * @param mixed $url
	 * @param mixed $status
	 * @param bool $exit
	 * @return void
	 */
	public function redirect($url, $status = null, $exit = true) {
		$this->redirectUrl = $url;
	}
}
