<?php

namespace Tools\Test\TestCase\Controller\Admin;

use Cake\TestSuite\IntegrationTestTrait;
use Shim\TestSuite\TestCase;
use Tools\Controller\Admin\HelperController;

#[\PHPUnit\Framework\Attributes\UsesClass(HelperController::class)]
class HelperControllerTest extends TestCase {

	use IntegrationTestTrait;

	/**
	 * @return void
	 */
	public function testBitmasks() {
		$this->disableErrorHandlerMiddleware();

		$this->get(['prefix' => 'Admin', 'plugin' => 'Tools', 'controller' => 'Helper', 'action' => 'bitmasks']);

		$this->assertResponseCode(200);
		$this->assertNoRedirect();
	}

}
