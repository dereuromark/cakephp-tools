<?php

namespace Tools\Test\TestCase\Controller;

use Cake\Core\Configure;
use Cake\ORM\TableRegistry;
use Shim\TestSuite\TestCase;
use Tools\Controller\Controller;

class ControllerTest extends TestCase {

	/**
	 * @var array
	 */
	protected $fixtures = [
		'plugin.Tools.ToolsUsers',
	];

	/**
	 * @var \Cake\Controller\Controller
	 */
	protected $Controller;

	/**
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		$this->Controller = new Controller();
		$this->Controller->startupProcess();
	}

	/**
	 * @return void
	 */
	public function tearDown(): void {
		parent::tearDown();

		unset($this->Controller);
	}

	/**
	 * @return void
	 */
	public function testPaginate() {
		Configure::write('Paginator.limit', 2);

		$ToolsUser = TableRegistry::getTableLocator()->get('ToolsUsers');

		$count = $ToolsUser->find()->count();
		$this->assertTrue($count > 3);

		$this->Controller->loadModel('ToolsUsers');
		$result = $this->Controller->paginate('ToolsUsers');
		$this->assertSame(2, count($result->toArray()));
	}

}
