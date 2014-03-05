<?php

App::uses('CommonComponent', 'Tools.Controller/Component');
App::uses('Component', 'Controller');
App::uses('CakeSession', 'Model/Datasource');
App::uses('Controller', 'Controller');
App::uses('AppModel', 'Model');

/**
 */
class CommonComponentTest extends CakeTestCase {

	public $fixtures = array('core.cake_session', 'plugin.tools.tools_user', 'plugin.tools.role');

	public function setUp() {
		parent::setUp();

		// BUGFIX for CakePHP2.5 - One has to write to the session before deleting actually works
		CakeSession::write('Auth', '');
		CakeSession::delete('Auth');

		$this->Controller = new CommonComponentTestController(new CakeRequest, new CakeResponse);
		$this->Controller->constructClasses();
		$this->Controller->startupProcess();
	}

	public function tearDown() {
		parent::tearDown();

		unset($this->Controller->Common);
		unset($this->Controller);
	}

	/**
	 * CommonComponentTest::testLoadHelper()
	 *
	 * @return void
	 */
	public function testLoadHelper() {
		$this->assertTrue(!in_array('Text', $this->Controller->helpers));
		$this->Controller->Common->loadHelper('Text');
		$this->assertTrue(in_array('Text', $this->Controller->helpers));
	}

	/**
	 * CommonComponentTest::testLoadComponent()
	 *
	 * @return void
	 */
	public function testLoadComponent() {
		$this->assertTrue(!isset($this->Controller->Test));
		$this->Controller->Common->loadComponent('Test');
		$this->assertTrue(isset($this->Controller->Test));

		// with plugin
		$this->Controller->Calendar = null;
		$this->assertTrue(!isset($this->Controller->Calendar));
		$this->Controller->Common->loadComponent('Tools.Calendar');
		$this->assertTrue(isset($this->Controller->Calendar));

		// with options
		$this->Controller->Test = null;
		$this->assertTrue(!isset($this->Controller->Test));
		$this->Controller->Common->loadComponent(array('RequestHandler', 'Test' => array('x' => 'y')));
		$this->assertTrue(isset($this->Controller->Test));
		$this->assertTrue($this->Controller->Test->isInit);
		$this->assertTrue($this->Controller->Test->isStartup);
	}

	/**
	 * CommonComponentTest::testLoadLib()
	 *
	 * @return void
	 */
	public function testLoadLib() {
		$this->assertTrue(!isset($this->Controller->RandomLib));
		$this->Controller->Common->loadLib('Tools.RandomLib');
		$this->assertTrue(isset($this->Controller->RandomLib));

		$res = $this->Controller->RandomLib->pwd(null, 10);
		$this->assertTrue(!empty($res));

		// with options
		$this->assertTrue(!isset($this->Controller->TestLib));
		$this->Controller->Common->loadLib(array('Tools.RandomLib', 'TestLib' => array('x' => 'y')));
		$this->assertTrue(isset($this->Controller->TestLib));
		$this->assertTrue($this->Controller->TestLib->hasOptions);
	}

	/**
	 * CommonComponentTest::testGetParams()
	 *
	 * @return void
	 */
	public function testGetParams() {
		$is = $this->Controller->Common->getNamedParam('x');
		$this->assertNull($is);

		$is = $this->Controller->Common->getNamedParam('x', 'y');
		$this->assertSame('y', $is);
	}

	/**
	 * CommonComponentTest::testGetDefaultUrlParams()
	 *
	 * @return void
	 */
	public function testGetDefaultUrlParams() {
		$is = $this->Controller->Common->defaultUrlParams();
		$this->assertNotEmpty($is);
	}

	/**
	 * CommonComponentTest::testcurrentUrl()
	 *
	 * @return void
	 */
	public function testCurrentUrl() {
		$is = $this->Controller->Common->currentUrl();
		$this->assertTrue(is_array($is) && !empty($is));

		$is = $this->Controller->Common->currentUrl(true);
		$this->assertTrue(!is_array($is) && !empty($is));
	}

	/**
	 * CommonComponentTest::testIsForeignReferer()
	 *
	 * @return void
	 */
	public function testIsForeignReferer() {
		$ref = 'http://www.spiegel.de';
		$is = $this->Controller->Common->isForeignReferer($ref);
		$this->assertTrue($is);

		$ref = Configure::read('App.fullBaseUrl') . '/some/controller/action';
		$is = $this->Controller->Common->isForeignReferer($ref);
		$this->assertFalse($is);

		$ref = '';
		$is = $this->Controller->Common->isForeignReferer($ref);
		$this->assertFalse($is);
	}

	/**
	 * CommonComponentTest::testTransientFlashMessage()
	 *
	 * @return void
	 */
	public function testTransientFlashMessage() {
		$is = $this->Controller->Common->transientFlashMessage('xyz', 'success');
		//$this->assertTrue($is);

		$res = Configure::read('messages');
		//debug($res);
		$this->assertTrue(!empty($res));
		$this->assertTrue(isset($res['success'][0]) && $res['success'][0] === 'xyz');
	}

	/**
	 * CommonComponentTest::testFlashMessage()
	 *
	 * @return void
	 */
	public function testFlashMessage() {
		$this->Controller->Session->delete('messages');
		$is = $this->Controller->Common->flashMessage('efg');

		$res = $this->Controller->Session->read('messages');
		$this->assertTrue(!empty($res));
		$this->assertTrue(isset($res['info'][0]) && $res['info'][0] === 'efg');
	}

	/**
	 * CommonComponentTest::testManualLogin()
	 *
	 * @return void
	 */
	public function testManualLogin() {
		$user = array(
			'name' => 'foo',
			'password' => 123,
			'role_id' => 1,
		);
		$User = ClassRegistry::init('MyToolsUser');
		$User->create();
		$res = $User->save($user);
		$this->assertTrue(!empty($res));

		$res = CakeSession::read('Auth');
		$this->assertNull($res);
		$is = $this->Controller->Common->manualLogin(2222);
		$this->assertFalse($is);

		$is = $this->Controller->Common->manualLogin($User->id);
		$this->assertTrue($is);

		$res = CakeSession::read('Auth');
		$this->assertSame($User->id, $res['User']['id']);
		$this->assertTrue(!empty($res['User']['Role']));
	}

	/**
	 * CommonComponentTest::testForceLogin()
	 *
	 * @return void
	 */
	public function testForceLogin() {
		$user = array(
			'name' => 'foo',
			'password' => 123,
			'role_id' => 1,
		);
		$User = ClassRegistry::init('MyToolsUser');
		$User->create();
		$res = $User->save($user);
		$this->assertTrue(!empty($res));

		$res = CakeSession::read('Auth');
		$this->assertNull($res);
		$is = $this->Controller->Common->forceLogin(2222);
		$this->assertFalse($is);
		$is = $this->Controller->Common->forceLogin($User->id);
		$this->assertTrue($is);

		$res = CakeSession::read('Auth');
		$this->assertSame($User->id, $res['User']['id']);
		$this->assertTrue(!empty($res['User']['Role']));
	}

	public function testGetGroup() {
		$list = array(
			'Models' => array(
				'1' => 'Foo',
				'2' => 'Bar'
			),
			'Mitarbeiter' => array(
				'3' => 'Some',
				'4' => 'Thing'
			),
		);
		$matching = array('Models' => 'Model', 'Mitarbeiter' => 'Contributor');

		$res = CommonComponent::getGroup($list, 111);
		$this->assertEquals('', $res);

		$res = CommonComponent::getGroup($list, 2);
		$this->assertEquals('Models', $res);

		$res = CommonComponent::getGroup($list, 2, $matching);
		$this->assertEquals('Model', $res);

		$res = CommonComponent::getGroup($list, 3, $matching);
		$this->assertEquals('Contributor', $res);
	}

	/**
	 * CommonComponentTest::testDataTrim()
	 *
	 * @return void
	 */
	public function testDataTrim() {
		$array = array('Some' => array('Deep' => array('array' => '  bla  ')));

		$this->Controller = new CommonComponentTestController(new CakeRequest, new CakeResponse);
		$this->Controller->request->data = $array;
		$this->Controller->request->query = $array;
		$this->Controller->constructClasses();
		$this->Controller->startupProcess();

		$expected = array('Some' => array('Deep' => array('array' => 'bla')));
		$this->assertSame($expected, $this->Controller->request->data);
		$this->assertSame($expected, $this->Controller->request->query);

		// Overwrite
		Configure::write('DataPreparation.notrim', true);

		$this->Controller = new CommonComponentTestController(new CakeRequest, new CakeResponse);
		$this->Controller->request->data = $array;
		$this->Controller->request->query = $array;
		$this->Controller->constructClasses();
		$this->Controller->startupProcess();

		$this->assertSame($array, $this->Controller->request->data);
		$this->assertSame($array, $this->Controller->request->query);
	}

}

/*** additional helper classes ***/

class MyToolsUser extends AppModel {

	public $useTable = 'tools_users';

	public $name = 'MyToolsUser';

	public $alias = 'User';

	public $belongsTo = array(
		'Role',
	);

}

// Use Controller instead of AppController to avoid conflicts
class CommonComponentTestController extends Controller {

	public $components = array('Session', 'Tools.Common', 'Auth');

	public $failed = false;

	public $testHeaders = array();

	public function fail() {
		$this->failed = true;
	}

	public function redirect($url, $status = null, $exit = true) {
		return $status;
	}

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
