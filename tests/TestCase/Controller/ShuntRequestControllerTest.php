<?php

namespace Tools\Test\TestCase\Controller;

use Cake\Core\Configure;
use Cake\TestSuite\IntegrationTestTrait;
use RuntimeException;
use Shim\TestSuite\TestCase;

/**
 * @uses \Tools\Controller\ShuntRequestController
 */
class ShuntRequestControllerTest extends TestCase {

	use IntegrationTestTrait;

	/**
	 * @var array
	 */
	protected $fixtures = [
		'core.Sessions',
	];

	/**
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		Configure::write('Config.allowedLanguages', []);
		Configure::write('Config.defaultLanguage', null);
	}

	/**
	 * @return void
	 */
	public function testLanguage() {
		$this->disableErrorHandlerMiddleware();

		Configure::write('Config.defaultLanguage', 'de');
		Configure::write('Config.allowedLanguages', [
			'de' => [
				'locale' => 'de_DE',
				'name' => 'Deutsch',
			],
		]);

		$this->post(['plugin' => 'Tools', 'controller' => 'ShuntRequest', 'action' => 'language']);

		$this->assertRedirect();
	}

	/**
	 * @return void
	 */
	public function testLanguageError() {
		$this->disableErrorHandlerMiddleware();

		$this->expectException(RuntimeException::class);

		$this->post(['plugin' => 'Tools', 'controller' => 'ShuntRequest', 'action' => 'language']);
	}

}
