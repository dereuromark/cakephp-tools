<?php

namespace Tools\Test\TestCase\Utility;

use Cake\Core\Configure;
use Cake\Http\Client;
use Cake\Http\Client\Request;
use Psr\Log\LogLevel;
use Shim\TestSuite\TestCase;
use Tools\Utility\GitterLog;

/**
 * GitterLogTest class
 */
class GitterLogTest extends TestCase {

	/**
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		$key = env('GITTER_KEY') ?: '123';
		Configure::write('Gitter.key', $key);
	}

	/**
	 * @return void
	 */
	public function tearDown(): void {
		parent::tearDown();

		Configure::delete('Gitter.key');
	}

	/**
	 * testLogsIntoDefaultFile method
	 *
	 * @return void
	 */
	public function testLogsIntoDefaultFile(): void {
		$mockClient = $this->getMockBuilder(Client::class)->onlyMethods(['send'])->getMock();

		$callback = function(Request $value) {
			return (string)$value->getBody() === 'message=Test%3A+It+%2Aworks%2A+with+some+error+%5Bmarkup%5D%28https%3A%2F%2Fmy-url.com%29%21&level=error';
		};
		$mockClient->expects($this->once())->method('send')->with($this->callback($callback));

		$gitterLog = $this->getMockBuilder(GitterLog::class)->onlyMethods(['getClient'])->getMock();
		$gitterLog->expects($this->once())->method('getClient')->willReturn($mockClient);

		$gitterLog->write('Test: It *works* with some error [markup](https://my-url.com)!', LogLevel::ERROR);
	}

}
