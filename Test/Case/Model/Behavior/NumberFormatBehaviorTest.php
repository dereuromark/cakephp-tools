<?php
App::uses('NumberFormatBehavior', 'Tools.Model/Behavior');
App::uses('MyCakeTestCase', 'Tools.TestSuite');

class NumberFormatBehaviorTest extends MyCakeTestCase {

	public $fixtures = array('plugin.tools.payment_method');

	public $Model;

	public function setUp() {
		parent::setUp();

		$this->Model = ClassRegistry::init('PaymentMethod');

		$this->Model->Behaviors->load('Tools.NumberFormat', array('fields' => array('rel_rate', 'set_rate'), 'output' => true));
	}

	public function tearDown() {
		parent::tearDown();

		unset($this->Model);
	}

	public function testObject() {
		$this->assertInstanceOf('NumberFormatBehavior', $this->Model->Behaviors->NumberFormat);
	}

	public function testBasic() {
		//echo $this->_header(__FUNCTION__);
		$data = array(
			'name' => 'some Name',
			'set_rate' => '0,1',
			'rel_rate' => '-0,02',
		);
		$this->Model->set($data);
		$res = $this->Model->validates();
		$this->assertTrue($res);

		$res = $this->Model->data;
		//debug($res);
		$this->assertSame($res[$this->Model->alias]['set_rate'], 0.1);
		$this->assertSame($res[$this->Model->alias]['rel_rate'], -0.02);
	}

	public function testValidates() {
		//echo $this->_header(__FUNCTION__);
		$data = array(
			'name' => 'some Name',
			'set_rate' => '0,1',
			'rel_rate' => '-0,02',
		);
		$this->Model->set($data);
		$res = $this->Model->validates();
		$this->assertTrue($res);

		$res = $this->Model->data;
		//debug($res);
		$this->assertSame($res[$this->Model->alias]['set_rate'], 0.1);
		$this->assertSame($res[$this->Model->alias]['rel_rate'], -0.02);
	}

	public function testFind() {
		//echo $this->_header(__FUNCTION__);
		$data = array(
			'name' => 'some Name',
			'set_rate' => '0,1',
			'rel_rate' => '-0,02',
		);
		$this->Model->create();
		$res = $this->Model->save($data);
		$this->assertTrue((bool)$res);

		// find all
		$res = $this->Model->find('all', array('order' => array('created' => 'DESC')));
		$this->assertTrue(!empty($res));
		$this->assertSame(substr($res[0][$this->Model->alias]['set_rate'], 0, 4), '0,10');
		$this->assertSame(substr($res[0][$this->Model->alias]['rel_rate'], 0, 5), '-0,02');

		// find first
		$res = $this->Model->find('first', array('order' => array('created' => 'DESC')));
		$this->assertTrue(!empty($res));
		$this->assertSame($res[$this->Model->alias]['set_rate'], '0,10');
		$this->assertSame($res[$this->Model->alias]['rel_rate'], '-0,0200');

		$res = $this->Model->find('count', array());
		$this->assertSame($res, 8);
	}

	public function testStrict() {
		$this->Model->Behaviors->unload('NumberFormat');
		$this->Model->Behaviors->load('Tools.NumberFormat', array('fields' => array('rel_rate', 'set_rate'), 'strict' => true));

		$data = array(
			'name' => 'some Name',
			'set_rate' => '0.1',
			'rel_rate' => '-0,02',
		);
		$this->Model->set($data);
		$res = $this->Model->validates();
		$this->assertTrue($res);

		$res = $this->Model->data;
		//debug($res);
		$this->assertSame($res[$this->Model->alias]['set_rate'], '0#1');
		$this->assertSame($res[$this->Model->alias]['rel_rate'], -0.02);
	}

	public function testBeforeSave() {
		$this->Model->Behaviors->unload('NumberFormat');
		$this->Model->Behaviors->load('Tools.NumberFormat', array('fields' => array('rel_rate', 'set_rate'), 'before' => 'save', 'output' => false));
		$data = array(
			'name' => 'some Name',
			'set_rate' => '2,11',
			'rel_rate' => '-1,22',
		);
		$this->Model->create();
		$res = $this->Model->save($data);
		$this->assertTrue((bool)$res);

		$res = $this->Model->find('first', array('order' => array('created' => 'DESC')));
		$this->assertTrue(!empty($res));
		$this->assertSame(substr($res[$this->Model->alias]['set_rate'], 0, 4), '2.11');
		$this->assertSame(substr($res[$this->Model->alias]['rel_rate'], 0, 5), '-1.22');
	}

	public function testLocaleConv() {
		$res = setlocale(LC_NUMERIC, 'de_DE.utf8', 'german');
		$this->skipIf(empty($res));

		$this->assertTrue(!empty($res));

		$this->Model->Behaviors->unload('NumberFormat');
		$this->Model->Behaviors->load('Tools.NumberFormat', array('fields' => array('rel_rate', 'set_rate'), 'localeconv' => true, 'output' => true));

		$data = array(
			'name' => 'german',
			'set_rate' => '3,11',
			'rel_rate' => '-4,22',
		);
		$this->Model->create();
		$res = $this->Model->save($data);
		$this->assertTrue((bool)$res);

		$res = $this->Model->find('first', array('conditions' => array('name' => 'german')));
		$this->assertTrue(!empty($res));
		$this->assertSame(substr($res[$this->Model->alias]['set_rate'], 0, 4), '3,11');
		$this->assertSame(substr($res[$this->Model->alias]['rel_rate'], 0, 5), '-4,22');

		$res = setlocale(LC_NUMERIC, 'en_US.utf8', 'english');
		$this->assertTrue(!empty($res));

		$this->Model->Behaviors->unload('NumberFormat');
		$this->Model->Behaviors->load('Tools.NumberFormat', array('fields' => array('rel_rate', 'set_rate'), 'localeconv' => true, 'output' => true));

		$data = array(
			'name' => 'english',
			'set_rate' => '3.21',
			'rel_rate' => '-4.32',
		);
		$this->Model->create();
		$res = $this->Model->save($data);
		$this->assertTrue((bool)$res);

		$res = $this->Model->find('first', array('conditions' => array('name' => 'english')));
		//debug($res);
		$this->assertTrue(!empty($res));
		$this->assertSame(substr($res[$this->Model->alias]['set_rate'], 0, 4), '3.21');
		$this->assertSame(substr($res[$this->Model->alias]['rel_rate'], 0, 5), '-4.32');
	}

	public function testMultiply() {
		$this->Model->Behaviors->unload('NumberFormat');
		$this->Model->Behaviors->load('Tools.NumberFormat', array('fields' => array('rel_rate', 'set_rate'), 'transform' => array(), 'multiply' => 0.01, 'output' => false));

		$data = array(
			'name' => 'multiply',
			'set_rate' => '122',
			'rel_rate' => '-2',
		);
		$this->Model->create();
		$res = $this->Model->save($data);
		$this->assertTrue((bool)$res);

		$res = $this->Model->find('first', array('conditions' => array('name' => 'multiply')));
		//debug($res);
		$this->assertTrue(!empty($res));
		$this->assertSame(substr($res[$this->Model->alias]['set_rate'], 0, 4), '1.22');
		$this->assertSame(substr($res[$this->Model->alias]['rel_rate'], 0, 5), '-0.02');

		$this->Model->Behaviors->unload('NumberFormat');
		$this->Model->Behaviors->load('Tools.NumberFormat', array('fields' => array('rel_rate', 'set_rate'), 'transform' => array(), 'multiply' => 0.01, 'output' => true));

		$res = $this->Model->find('first', array('conditions' => array('name' => 'multiply')));
		//debug($res);
		$this->assertTrue(!empty($res));
		$this->assertSame($res[$this->Model->alias]['set_rate'], '122');
		$this->assertSame($res[$this->Model->alias]['rel_rate'], '-2');
	}

}
