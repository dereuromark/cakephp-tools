<?php

namespace Tools\Test\TestCase\Controller\Admin;

use Cake\TestSuite\IntegrationTestTrait;
use PHPUnit\Framework\Attributes\UsesClass;
use Shim\TestSuite\TestCase;
use Tools\Controller\Admin\PagesController;

#[UsesClass(PagesController::class)]
class PagesControllerTest extends TestCase {

	use IntegrationTestTrait;

	/**
	 * @return void
	 */
	public function testIndex() {
		$this->disableErrorHandlerMiddleware();

		$this->get(['prefix' => 'Admin', 'plugin' => 'Tools', 'controller' => 'Pages', 'action' => 'index']);

		$this->assertResponseCode(200);
		$this->assertNoRedirect();
	}

}
