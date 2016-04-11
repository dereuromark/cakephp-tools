<?php

namespace Tools\Test\TestCase\Controller;

use Cake\Core\Configure;
use Cake\ORM\TableRegistry;
use Tools\Controller\Controller;
use Tools\TestSuite\TestCase;

/**
 */
class ControllerTest extends TestCase {

	/**
	 * @var array
	 */
	public $fixtures = ['plugin.Tools.ToolsUsers'];

	/**
	 * @var Cake\Controller\Controller
	 */
	public $Controller;

	/**
	 * @return void
	 */
	public function setUp() {
		parent::setUp();

		Configure::write('App.namespace', 'TestApp');

		$this->Controller = new Controller();
		$this->Controller->startupProcess();
	}

	/**
	 * @return void
	 */
	public function tearDown() {
		parent::tearDown();

		unset($this->Controller);
	}

	/**
	 * @return void
	 */
	public function testPaginate() {
		Configure::write('Paginator.limit', 2);

		$ToolsUser = TableRegistry::get('ToolsUsers');

		$count = $ToolsUser->find('count');
		$this->assertTrue($count > 3);

		$this->Controller->loadModel('ToolsUsers');
		$result = $this->Controller->paginate('ToolsUsers');
		$this->assertSame(2, count($result->toArray()));
	}

}
