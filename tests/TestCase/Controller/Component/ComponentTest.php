<?php
namespace Tools\Test\TestCase\Controller\Component;

use Cake\Controller\ComponentRegistry;
use Cake\Controller\Controller;
use Tools\Controller\Component\Component;
use Cake\Network\Request;
use Tools\TestSuite\TestCase;
use Cake\Event\Event;
/**
 * SessionComponentTest class
 *
 */
class ComponentTest extends TestCase {

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		$this->Controller = new Controller(new Request());
		$this->ComponentRegistry = new ComponentRegistry($this->Controller);
	}

/**
 * testBeforeFilter method
 *
 * @return void
 */
	public function testBeforeFilter() {
		$Component = new Component($this->ComponentRegistry);
		$event = new Event('Controller.startup', $this->Controller);
		$Component->beforeFilter($event);

		$this->assertInstanceOf('Cake\Controller\Controller', $Component->Controller);
	}

}
