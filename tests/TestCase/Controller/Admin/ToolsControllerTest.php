<?php

namespace Tools\Test\TestCase\Controller\Admin;

use Cake\TestSuite\IntegrationTestTrait;
use Shim\TestSuite\TestCase;
use Tools\Controller\Admin\ToolsController;

#[\PHPUnit\Framework\Attributes\UsesClass(ToolsController::class)]
class ToolsControllerTest extends TestCase {

	use IntegrationTestTrait;

	/**
	 * @return void
	 */
	public function testIcons() {
		$this->disableErrorHandlerMiddleware();

		$this->get(['prefix' => 'Admin', 'plugin' => 'Tools', 'controller' => 'Tools', 'action' => 'index']);

		$this->assertResponseCode(200);
	}

}
