<?php

namespace Tools\Test\TestCase\Controller\Admin;

use Cake\Core\Configure;
use Cake\TestSuite\IntegrationTestTrait;
use Shim\TestSuite\TestCase;
use Tools\View\Icon\BootstrapIcon;

/**
 * @uses \Tools\Controller\Admin\FormatController
 */
class FormatControllerTest extends TestCase {

	use IntegrationTestTrait;

	/**
	 * @return void
	 */
	public function testIcons() {
		$this->disableErrorHandlerMiddleware();

		Configure::write('Icon', [
			'sets' => [
				'bs' => BootstrapIcon::class,
			],
		]);
		$this->get(['prefix' => 'Admin', 'plugin' => 'Tools', 'controller' => 'Format', 'action' => 'icons']);

		$this->assertResponseCode(200);
	}

}
