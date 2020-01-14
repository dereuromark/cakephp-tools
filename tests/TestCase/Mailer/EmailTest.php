<?php

namespace Tools\Test\TestCase\Mailer;

use Cake\Core\Configure;
use Cake\Log\Log;
use Cake\Mailer\TransportFactory;
use Shim\TestSuite\TestCase;
use Tools\Mailer\Email;

/**
 * EmailTest class
 */
class EmailTest extends TestCase {

	/**
	 * @var \TestApp\Mailer\TestEmail
	 */
	protected $Email;

	/**
	 * setUp
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		$this->skipIf(true, 'Will be removed');

		TransportFactory::setConfig('debug', [
			'className' => 'Debug',
		]);

		Configure::delete('Config.xMailer');
	}

	/**
	 * tearDown method
	 *
	 * @return void
	 */
	public function tearDown(): void {
		parent::tearDown();
		Log::drop('email');
		//Email::drop('test');
		TransportFactory::drop('debug');
		TransportFactory::drop('test_smtp');

		Configure::delete('Config.xMailer');
	}

	/**
	 * @return void
	 */
	public function testSetProfile() {
		Configure::write('Config.xMailer', 'foobar');

		$this->Email->setProfile('default');

		$result = $this->Email->getMessage()->getHeaders();
		$this->assertArrayHasKey('X-Mailer', $result);
		$this->assertSame('foobar', $result['X-Mailer']);
	}

}
