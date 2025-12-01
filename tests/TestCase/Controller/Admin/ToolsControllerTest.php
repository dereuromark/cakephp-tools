<?php

namespace Tools\Test\TestCase\Controller\Admin;

use Cake\TestSuite\IntegrationTestTrait;
use PHPUnit\Framework\Attributes\UsesClass;
use Shim\TestSuite\TestCase;
use Tools\Controller\Admin\ToolsController;

#[UsesClass(ToolsController::class)]
class ToolsControllerTest extends TestCase {

	use IntegrationTestTrait;

	/**
	 * @return void
	 */
	public function testIndex() {
		$this->disableErrorHandlerMiddleware();

		$this->get(['prefix' => 'Admin', 'plugin' => 'Tools', 'controller' => 'Tools', 'action' => 'index']);

		$this->assertResponseCode(200);
		$this->assertNoRedirect();
	}

}
