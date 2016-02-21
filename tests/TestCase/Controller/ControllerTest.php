<?php

namespace Tools\Test\TestCase\Controller;

use Cake\Controller\ComponentRegistry;
use Cake\Controller\Component\CommonComponent;
use Cake\Core\Configure;
use Cake\Network\Request;
use Cake\Network\Session;
use Cake\ORM\TableRegistry;
use Shim\Controller\Component\Component;
use Tools\Controller\Controller;
use Tools\TestSuite\TestCase;

/**
 */
class ControllerTest extends TestCase {

	public $fixtures = ['plugin.Tools.ToolsUsers'];

	public $Controller;

	public function setUp() {
		parent::setUp();

		Configure::write('App.namespace', 'TestApp');

		$this->Controller = new Controller();
		$this->Controller->startupProcess();
	}

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
