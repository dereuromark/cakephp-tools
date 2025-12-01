<?php

namespace Tools\Test\TestCase\Controller\Admin;

use Cake\TestSuite\IntegrationTestTrait;
use PHPUnit\Framework\Attributes\UsesClass;
use Shim\TestSuite\TestCase;
use Tools\Controller\Admin\HelperController;

#[UsesClass(HelperController::class)]
class HelperControllerTest extends TestCase {

	use IntegrationTestTrait;

	/**
	 * @return void
	 */
	public function testChars() {
		$this->disableErrorHandlerMiddleware();

		$this->get(['prefix' => 'Admin', 'plugin' => 'Tools', 'controller' => 'Helper', 'action' => 'chars']);

		$this->assertResponseCode(200);
		$this->assertNoRedirect();
	}

	/**
	 * @return void
	 */
	public function testCharsPost() {
		$this->disableErrorHandlerMiddleware();

		$data = [
			'string' => 'Some string',
		];
		$this->post(['prefix' => 'Admin', 'plugin' => 'Tools', 'controller' => 'Helper', 'action' => 'chars'], $data);

		$this->assertResponseCode(200);
		$this->assertNoRedirect();
	}

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
