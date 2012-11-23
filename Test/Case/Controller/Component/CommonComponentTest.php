<?php

App::uses('CommonComponent', 'Tools.Controller/Component');
App::uses('Component', 'Controller');
App::uses('AppController', 'Controller');

/**
 * 2010-11-10 ms
 */
class CommonComponentTest extends CakeTestCase {
/**
 * setUp method
 *
 * @access public
 * @return void
 */
	public function setUp() {
		$this->Controller = new CommonComponentTestController(new CakeRequest, new CakeResponse);
		$this->Controller->constructClasses();
		$this->Controller->startupProcess();
	}
/**
 * Tear-down method. Resets environment state.
 *
 * @access public
 * @return void
 */
	public function tearDown() {
		unset($this->Controller->Common);
		unset($this->Controller);
	}


	public function testLoadHelper() {
		$this->assertTrue(!in_array('Text', $this->Controller->helpers));
		$this->Controller->Common->loadHelper('Text');
		$this->assertTrue(in_array('Text', $this->Controller->helpers));


	}

	public function testLoadComponent() {
		$this->assertTrue(!isset($this->Controller->Test));
		$this->Controller->Common->loadComponent('Test');
		$this->assertTrue(isset($this->Controller->Test));

		# with plugin
		$this->Controller->Calendar = null;
		$this->assertTrue(!isset($this->Controller->Calendar));
		$this->Controller->Common->loadComponent('Tools.Calendar');
		$this->assertTrue(isset($this->Controller->Calendar));

		# with options
		$this->Controller->Test = null;
		$this->assertTrue(!isset($this->Controller->Test));
		$this->Controller->Common->loadComponent(array('RequestHandler', 'Test'=>array('x'=>'y')));
		$this->assertTrue(isset($this->Controller->Test));
		$this->assertTrue($this->Controller->Test->isInit);
		$this->assertTrue($this->Controller->Test->isStartup);
	}

	public function testLoadLib() {
		$this->assertTrue(!isset($this->Controller->RandomLib));
		$this->Controller->Common->loadLib('Tools.RandomLib');
		$this->assertTrue(isset($this->Controller->RandomLib));

		$res = $this->Controller->RandomLib->pwd(null, 10);
		$this->assertTrue(!empty($res));

		# with options
		$this->assertTrue(!isset($this->Controller->TestLib));
		$this->Controller->Common->loadLib(array('Tools.RandomLib', 'TestLib'=>array('x'=>'y')));
		$this->assertTrue(isset($this->Controller->TestLib));
		$this->assertTrue($this->Controller->TestLib->hasOptions);
	}



	public function testGetParams() {
		$is = $this->Controller->Common->getQueryParam('case');
		$this->assertTrue(strpos($is, 'CommonComponent') > 0 || $is == 'AllComponentTests' || $is == 'AllPluginTests');

		$is = $this->Controller->Common->getQueryParam('x');
		$this->assertSame(null, $is);

		$is = $this->Controller->Common->getQueryParam('x', 'y');
		$this->assertSame($is, 'y');

		$is = $this->Controller->Common->getNamedParam('plugin');
		$this->assertSame(null, $is);

		$is = $this->Controller->Common->getNamedParam('x');
		$this->assertSame(null, $is);

		$is = $this->Controller->Common->getNamedParam('x', 'y');
		$this->assertSame($is, 'y');

	}

	public function testGetDefaultUrlParams() {
		$is = $this->Controller->Common->defaultUrlParams();
		debug($is);
		$this->assertNotEmpty($is);
	}


	public function testTransientFlashMessage() {
		$is = $this->Controller->Common->transientFlashMessage('xyz', 'success');
		//$this->assertTrue($is);

		$res = Configure::read('messages');
		debug($res);
		$this->assertTrue(!empty($res));
		$this->assertTrue(isset($res['success'][0]) && $res['success'][0] == 'xyz');
	}


	public function testFlashMessage() {
		$this->Controller->Session->delete('messages');
		$is = $this->Controller->Common->flashMessage('efg');
		//$this->assertTrue($is);

		$res = $this->Controller->Session->read('messages');
		debug($res);
		$this->assertTrue(!empty($res));
		$this->assertTrue(isset($res['info'][0]) && $res['info'][0] == 'efg');
	}



}



/*** additional helper classes ***/


/**
* Short description for class.
*
* @package cake.tests
* @subpackage cake.tests.cases.libs.controller.components
*/
class CommonComponentTestController extends AppController {
/**
 * name property
 *
 * @var string 'SecurityTest'
 * @access public
 */

/**
 * components property
 *
 * @var array
 * @access public
 */
	public $components = array('Tools.Common');
/**
 * failed property
 *
 * @var bool false
 * @access public
 */
	public $failed = false;
/**
 * Used for keeping track of headers in test
 *
 * @var array
 * @access public
 */
	public $testHeaders = array();
/**
 * fail method
 *
 * @access public
 * @return void
 */
	public function fail() {
		$this->failed = true;
	}
/**
 * redirect method
 *
 * @param mixed $option
 * @param mixed $code
 * @param mixed $exit
 * @access public
 * @return void
 */
	public function redirect($url, $status = null, $exit = true) {
		return $status;
	}
/**
 * Conveinence method for header()
 *
 * @param string $status
 * @return void
 * @access public
 */
	public function header($status) {
		$this->testHeaders[] = $status;
	}
}


class TestComponent extends Component {

	public $Controller;
	public $isInit = false;
	public $isStartup = false;

	public function initialize(Controller $Controller) {
		//$this->Controller = $Controller;
		$this->isInit = true;
	}

	public function startup(Controller $Controller) {
		//$this->Controller = $Controller;
		$this->isStartup = true;
	}

}

class TestHelper extends Object {


}

class TestLib {

	public $hasOptions = false;

	public function __construct($options = array()) {
		if (!empty($options)) {
			$this->hasOptions = true;
		}
	}
}


