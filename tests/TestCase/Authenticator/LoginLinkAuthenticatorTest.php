<?php

namespace Tools\Test\TestCase\Authenticator;

use ArrayAccess;
use ArrayObject;
use Authentication\Authenticator\JwtAuthenticator;
use Authentication\Authenticator\Result;
use Authentication\Identifier\IdentifierCollection;
use Cake\Http\ServerRequestFactory;
use Cake\TestSuite\TestCase;
use Tools\Authenticator\LoginLinkAuthenticator;

class LoginLinkAuthenticatorTest extends TestCase
{
	/**
	 * Fixtures
	 *
	 * @var array
	 */
	protected array $fixtures = [
		'plugin.tools.ToolsUsers',
		'plugin.tools.Tokens',
	];

	/**
	 * Identifier Collection
	 *
	 * @var \Authentication\Identifier\IdentifierCollection;
	 */
	public $identifiers;

	/**
	 * @var \Cake\Http\ServerRequest
	 */
	protected $request;

	/**
	 * @inheritDoc
	 */
	public function setUp(): void
	{
		parent::setUp();

		$this->identifiers = new IdentifierCollection([
			'Authentication.JwtSubject' => [
				'resolver' => [
					'className' => 'Authentication.Orm',
					'userModel' => 'ToolsUsers',
				],
			],
		]);
	}

	/**
	 * @return void
	 */
	public function testAuthenticate()
	{
		$user = $this->fetchTable('ToolsUsers')->find()->firstOrFail();

		$tokensTable = $this->fetchTable('Tools.Tokens');
		$tokensTable->newKey('login_link', '123', $user->id);

		$this->request = ServerRequestFactory::fromGlobals(
			['REQUEST_URI' => '/'],
		);
		$this->request = $this->request->withQueryParams([
			'token' => '123',
		]);

		$authenticator = new LoginLinkAuthenticator($this->identifiers);

		$result = $authenticator->authenticate($this->request);
		$this->assertInstanceOf(Result::class, $result);
		$this->assertSame(Result::SUCCESS, $result->getStatus());
		$this->assertInstanceOf(ArrayAccess::class, $result->getData());
	}
}
