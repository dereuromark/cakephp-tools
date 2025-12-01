<?php

namespace Tools\Test\TestCase\Controller;

use Cake\Core\Configure;
use Cake\TestSuite\IntegrationTestTrait;
use PHPUnit\Framework\Attributes\UsesClass;
use RuntimeException;
use Shim\TestSuite\TestCase;
use Tools\Controller\ShuntRequestController;

#[UsesClass(ShuntRequestController::class)]
class ShuntRequestControllerTest extends TestCase {

	use IntegrationTestTrait;

	/**
	 * @var array
	 */
	protected array $fixtures = [
		'plugin.Tools.Sessions',
	];

	/**
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		$this->loadPlugins(['Tools']);
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
