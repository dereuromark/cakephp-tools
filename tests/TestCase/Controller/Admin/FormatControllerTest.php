<?php

namespace Tools\Test\TestCase\Controller\Admin;

use Cake\TestSuite\IntegrationTestTrait;
use Shim\TestSuite\TestCase;

/**
 * @uses \Tools\Controller\Admin\FormatController
 */
class FormatControllerTest extends TestCase {

	use IntegrationTestTrait;

	/**
	 * @var array
	 */
	protected $fixtures = [
		//'core.Sessions',
	];

	/**
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();
	}

	/**
	 * @return void
	 */
	public function testIcons() {
		$this->disableErrorHandlerMiddleware();

		$this->get(['prefix' => 'Admin', 'plugin' => 'Tools', 'controller' => 'Format', 'action' => 'icons']);

		$this->assertResponseCode(200);
	}

}
