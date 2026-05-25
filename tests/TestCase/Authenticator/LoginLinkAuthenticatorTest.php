<?php

namespace Tools\Test\TestCase\Authenticator;

use ArrayAccess;
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

}
