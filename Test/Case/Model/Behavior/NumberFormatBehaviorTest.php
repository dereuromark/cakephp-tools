<?php
App::uses('NumberFormatBehavior', 'Tools.Model/Behavior');
App::uses('MyCakeTestCase', 'Tools.TestSuite');

class NumberFormatBehaviorTest extends MyCakeTestCase {

	public $fixtures = array('plugin.tools.payment_method');

	public $Model;

	public function setUp() {
		parent::setUp();

		Configure::delete('Localization');
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

	/**
	 * NumberFormatBehaviorTest::testFind()
	 *
	 * @return void
	 */
	public function testFind() {
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
		$this->assertSame('0,10', substr($res[0][$this->Model->alias]['set_rate'], 0, 4));
		$this->assertSame('-0,02', substr($res[0][$this->Model->alias]['rel_rate'], 0, 5));

		// find first
		$res = $this->Model->find('first', array('order' => array('created' => 'DESC')));
		$this->assertTrue(!empty($res));
		$this->assertSame('0,10', $res[$this->Model->alias]['set_rate']);
		$this->assertSame('-0,0200', $res[$this->Model->alias]['rel_rate']);

		$res = $this->Model->find('count', array());
		$this->assertSame(8, $res);
	}

	/**
	 * NumberFormatBehaviorTest::testStrict()
	 *
	 * @return void
	 */
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
		$this->assertSame('0#1', $res[$this->Model->alias]['set_rate']);
		$this->assertSame(-0.02, $res[$this->Model->alias]['rel_rate']);
	}

	/**
	 * NumberFormatBehaviorTest::testBeforeSave()
	 *
	 * @return void
	 */
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
		$this->assertSame('2.11', substr($res[$this->Model->alias]['set_rate'], 0, 4));
		$this->assertSame('-1.22', substr($res[$this->Model->alias]['rel_rate'], 0, 5));
	}

	/**
	 * NumberFormatBehaviorTest::testLocaleConv()
	 *
	 * @return void
	 */
	public function testLocaleConv() {
		$res = setlocale(LC_NUMERIC, 'de_DE.utf8', 'german');
		$this->skipIf(empty($res), 'No valid locale found.');

		$this->assertTrue(!empty($res));

		$conv = localeconv();
		$this->skipIf(empty($conv['thousands_sep']), 'No thousands separator in this locale.');

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
		//debug($res);ob_flush();
		$this->assertSame('3,11', substr($res[$this->Model->alias]['set_rate'], 0, 4));
		$this->assertSame('-4,22', substr($res[$this->Model->alias]['rel_rate'], 0, 5));

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
		$this->assertSame('3.21', substr($res[$this->Model->alias]['set_rate'], 0, 4));
		$this->assertSame('-4.32', substr($res[$this->Model->alias]['rel_rate'], 0, 5));
	}

	/**
	 * NumberFormatBehaviorTest::testMultiply()
	 *
	 * @return void
	 */
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
		$this->assertSame('1.22', substr($res[$this->Model->alias]['set_rate'], 0, 4));
		$this->assertSame('-0.02', substr($res[$this->Model->alias]['rel_rate'], 0, 5));

		$this->Model->Behaviors->unload('NumberFormat');
		$this->Model->Behaviors->load('Tools.NumberFormat', array('fields' => array('rel_rate', 'set_rate'), 'transform' => array(), 'multiply' => 0.01, 'output' => true));

		$res = $this->Model->find('first', array('conditions' => array('name' => 'multiply')));
		//debug($res);
		$this->assertTrue(!empty($res));
		$this->assertSame('122', $res[$this->Model->alias]['set_rate']);
		$this->assertSame('-2', $res[$this->Model->alias]['rel_rate']);
	}

}
