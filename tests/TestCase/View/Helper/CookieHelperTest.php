<?php

namespace Tools\TestCase\View\Helper;

use Tools\View\Helper\CookieHelper;
use Tools\TestSuite\TestCase;
use Cake\View\View;
use Cake\ORM\Entity;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Datasource\ConnectionManager;
use Cake\Network\Request;

class CookieHelperTest extends TestCase {

	public $Cookie;

	/**
	 * @return void
	 */
	public function setUp() {
		parent::setUp();

		$this->Cookie = new CookieHelper(new View(null));
		$this->Cookie->request = $this->getMock('Cake\Network\Request', ['cookie']);
	}

	public function tearDown() {
		unset($this->Table);

		parent::tearDown();
	}

	/**
	 * CookieHelperTest::testObject()
	 *
	 * @return void
	 */
	public function testObject() {
		$this->assertInstanceOf('Tools\View\Helper\CookieHelper', $this->Cookie);
	}

	/**
	 * CookieHelperTest::testCheck()
	 *
	 * @return void
	 */
	public function testCheck() {
		$this->Cookie->request->expects($this->at(0))
			->method('cookie')
			->will($this->returnValue(null));
		$this->Cookie->request->expects($this->at(1))
			->method('cookie')
			->will($this->returnValue('val'));

		$this->assertFalse($this->Cookie->check('Foo.key'));
		$this->assertTrue($this->Cookie->check('Foo.key'));
	}

	/**
	 * CookieHelperTest::testRead()
	 *
	 * @return void
	 */
	public function testRead() {
		$this->Cookie->request->expects($this->once())
			->method('cookie')
			->will($this->returnValue('val'));

		$output = $this->Cookie->read('Foo.key');
		$this->assertTextEquals('val', $output);
	}

}
