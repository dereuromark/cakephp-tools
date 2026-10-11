<?php

namespace Tools\Test\TestCase\Authenticator;

use ArrayAccess;
use Authentication\AuthenticationService;
use Authentication\Authenticator\Result;
use Cake\Http\ServerRequestFactory;
use Cake\TestSuite\TestCase;
use Tools\Authenticator\LoginLinkAuthenticator;
use Tools\Identifier\LoginLinkIdentifier;

class LoginLinkAuthenticatorTest extends TestCase {

	/**
	 * Fixtures
	 *
	 * @var array
	 */
	protected array $fixtures = [
		'plugin.Tools.ToolsUsers',
		'plugin.Tools.Tokens',
	];

	/**
	 * @var \Tools\Identifier\LoginLinkIdentifier
	 */
	protected $identifier;

	/**
	 * @var \Cake\Http\ServerRequest
	 */
	protected $request;

	/**
	 * @inheritDoc
	 */
	public function setUp(): void {
		parent::setUp();

		$this->identifier = new LoginLinkIdentifier([
			'resolver' => [
				'className' => 'Authentication.Orm',
				'userModel' => 'ToolsUsers',
			],
		]);
	}

	/**
	 * @return void
	 */
	public function testAuthenticate() {
		$user = $this->fetchTable('ToolsUsers')->find()->firstOrFail();

		$tokensTable = $this->fetchTable('Tools.Tokens');
		$tokensTable->newKey('login_link', '123', $user->id);

		$this->request = ServerRequestFactory::fromGlobals(
			['REQUEST_URI' => '/'],
		);
		$this->request = $this->request->withQueryParams([
			'token' => '123',
		]);

		$authenticator = new LoginLinkAuthenticator($this->identifier);

		$result = $authenticator->authenticate($this->request);
		$this->assertInstanceOf(Result::class, $result);
		$this->assertSame(Result::SUCCESS, $result->getStatus());
		$this->assertInstanceOf(ArrayAccess::class, $result->getData());
	}

	/**
	 * @return void
	 */
	public function testMalformedTokenIsMissing(): void {
		$request = ServerRequestFactory::fromGlobals(['REQUEST_URI' => '/'])
			->withQueryParams(['token' => ['123']]);
		$result = (new LoginLinkAuthenticator($this->identifier))->authenticate($request);
		$this->assertSame(Result::FAILURE_CREDENTIALS_MISSING, $result->getStatus());
	}

	/**
	 * @return void
	 */
	public function testLoginRouteIncludesPluginAndPrefix(): void {
		$request = ServerRequestFactory::fromGlobals(['REQUEST_URI' => '/'])
			->withQueryParams(['token' => '123'])
			->withAttribute('params', ['plugin' => null, 'prefix' => null, 'controller' => 'Users', 'action' => 'login']);
		$authenticator = new LoginLinkAuthenticator($this->identifier, [
			'loginUrl' => ['plugin' => 'Accounts', 'prefix' => 'Admin', 'controller' => 'Users', 'action' => 'login'],
		]);
		$this->assertSame(Result::FAILURE_CREDENTIALS_MISSING, $authenticator->authenticate($request)->getStatus());
	}

	/**
	 * @return void
	 */
	public function testIncompleteLoginRouteRejectsToken(): void {
		$request = ServerRequestFactory::fromGlobals(['REQUEST_URI' => '/users/edit'])
			->withQueryParams(['token' => '123'])
			->withAttribute('params', ['controller' => 'Users', 'action' => 'edit']);
		foreach ([['controller' => 'Users'], ['action' => 'edit']] as $loginUrl) {
			$authenticator = new LoginLinkAuthenticator($this->identifier, ['loginUrl' => $loginUrl]);
			$this->assertSame(Result::FAILURE_CREDENTIALS_MISSING, $authenticator->authenticate($request)->getStatus());
		}
	}

	/**
	 * @return void
	 */
	public function testConfiguredUrlCheckerRejectsDifferentPath(): void {
		$request = ServerRequestFactory::fromGlobals(['REQUEST_URI' => '/other'])
			->withQueryParams(['token' => '123']);
		$authenticator = new LoginLinkAuthenticator($this->identifier, [
			'urlChecker' => 'Authentication.Default',
			'loginUrl' => '/login',
		]);
		$this->assertSame(Result::FAILURE_CREDENTIALS_MISSING, $authenticator->authenticate($request)->getStatus());
	}

	/**
	 * @return void
	 */
	public function testApplicationRouteAcceptsExplicitFalsePluginAndPrefix(): void {
		$user = $this->fetchTable('ToolsUsers')->find()->firstOrFail();
		$this->fetchTable('Tools.Tokens')->newKey('login_link', 'route-token', $user->id);
		$request = ServerRequestFactory::fromGlobals(['REQUEST_URI' => '/users/login'])
			->withQueryParams(['token' => 'route-token'])
			->withAttribute('params', ['plugin' => null, 'prefix' => null, 'controller' => 'Users', 'action' => 'login']);
		$authenticator = new LoginLinkAuthenticator($this->identifier, [
			'loginUrl' => ['plugin' => false, 'prefix' => false, 'controller' => 'Users', 'action' => 'login'],
		]);
		$this->assertSame(Result::SUCCESS, $authenticator->authenticate($request)->getStatus());
	}

	/**
	 * @return void
	 */
	public function testLegacyUrlCheckerName(): void {
		$request = ServerRequestFactory::fromGlobals(['REQUEST_URI' => '/other'])
			->withQueryParams(['token' => '123']);
		$authenticator = new LoginLinkAuthenticator($this->identifier, [
			'urlChecker' => 'Authentication.CakeRouter',
			'loginUrl' => '/login',
		]);
		$this->assertSame(Result::FAILURE_CREDENTIALS_MISSING, $authenticator->authenticate($request)->getStatus());
	}

	/**
	 * @return void
	 */
	public function testAuthenticationServiceIdentifierConfiguration(): void {
		$user = $this->fetchTable('ToolsUsers')->find()->firstOrFail();
		$this->fetchTable('Tools.Tokens')->newKey('login_link', 'service-token', $user->id);
		$service = new AuthenticationService();
		$service->loadAuthenticator('Tools.LoginLink', [
			'identifier' => [
				'className' => 'Tools.LoginLink',
				'resolver' => ['className' => 'Authentication.Orm', 'userModel' => 'ToolsUsers'],
			],
			'urlChecker' => ['className' => 'Authentication.CakeRouter'],
			'loginUrl' => '/login',
		]);
		$request = ServerRequestFactory::fromGlobals(['REQUEST_URI' => '/login'])
			->withQueryParams(['token' => 'service-token']);
		$this->assertSame(Result::SUCCESS, $service->authenticate($request)->getStatus());
	}

}
