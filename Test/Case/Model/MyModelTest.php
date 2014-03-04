<?php
App::uses('MyModel', 'Tools.Model');
App::uses('MyCakeTestCase', 'Tools.TestSuite');

class MyModelTest extends MyCakeTestCase {

	public $Post;

	public $User;

	public $modelName = 'User';

	public $fixtures = array('core.user', 'core.post', 'core.author');

	public function setUp() {
		parent::setUp();

		$this->Post = ClassRegistry::init('MyAppModelPost');

		$this->User = ClassRegistry::init('MyAppModelUser');
	}

	public function testObject() {
		$this->Post = ClassRegistry::init('MyModel');
		$this->assertTrue(is_object($this->Post));
		$this->assertInstanceOf('MyModel', $this->Post);
	}

	/**
	 * MyModelTest::testGet()
	 *
	 * @return void
	 */
	public function testGet() {
		$record = $this->Post->get(2);
		$this->assertEquals(2, $record['Post']['id']);

		$record = $this->Post->get(2, array('fields' => 'id', 'created'));
		$this->assertEquals(2, count($record['Post']));

		$record = $this->Post->get(2, array('fields' => 'id', 'title', 'body'), array('Author'));
		$this->assertEquals(3, $record['Author']['id']);
	}

	/**
	 * MyModelTest::testGetRelatedInUse()
	 *
	 * @return void
	 */
	public function testGetRelatedInUse() {
		$this->Post->Author->displayField = 'user';
		$results = $this->Post->getRelatedInUse('Author', 'author_id', 'list');
		$expected = array(1 => 'mariano', 3 => 'larry');
		$this->assertEquals($expected, $results);
	}

	/**
	 * MyModelTest::testGetFieldInUse()
	 *
	 * @return void
	 */
	public function testGetFieldInUse() {
		$results = $this->Post->getFieldInUse('author_id', 'list');
		$expected = array(1 => 'First Post', 2 => 'Second Post');
		$this->assertEquals($expected, $results);
	}

	/**
	 * MyModelTest::testEnum()
	 *
	 * @return void
	 */
	public function testEnum() {
		$array = array(
			1 => 'foo',
			2 => 'bar',
		);

		$res = AppTestModel::enum(null, $array, false);
		$this->assertEquals($array, $res);

		$res = AppTestModel::enum(2, $array, false);
		$this->assertEquals('bar', $res);

		$res = AppTestModel::enum('2', $array, false);
		$this->assertEquals('bar', $res);

		$res = AppTestModel::enum(3, $array, false);
		$this->assertFalse($res);
	}

	/**
	 * More tests in MyModel Test directly
	 *
	 * @return void
	 */
	public function testGetFalse() {
		$this->User->order = array();
		$is = $this->User->get('xyz');
		$this->assertSame(array(), $is);
	}

	/**
	 * Test auto inc value of the current table
	 *
	 * @return void
	 */
	public function testGetNextAutoIncrement() {
		$this->out($this->_header(__FUNCTION__), true);
		$is = $this->User->getNextAutoIncrement();
		$this->out(returns($is));

		$schema = $this->User->schema('id');
		if ($schema['length'] == 36) {
			$this->assertFalse($is);
		} else {
			$this->assertTrue(is_int($is));
		}
	}

	/**
	 * MyModelTest::testDeconstruct()
	 *
	 * @return void
	 */
	public function testDeconstruct() {
		$data = array('year' => '2010', 'month' => '10', 'day' => 11);
		$res = $this->User->deconstruct('User.dob', $data);
		$this->assertEquals('2010-10-11', $res);

		$res = $this->User->deconstruct('User.dob', $data, 'datetime');
		$this->assertEquals('2010-10-11 00:00:00', $res);
	}

	/**
	 * Test that strings are correctly escaped using '
	 *
	 * @return void
	 */
	public function testEscapeValue() {
		$res = $this->User->escapeValue(4);
		$this->assertSame(4, $res);

		$res = $this->User->escapeValue('4');
		$this->assertSame('4', $res);

		$res = $this->User->escapeValue('a');
		$this->assertSame('\'a\'', $res);

		$res = $this->User->escapeValue(true);
		$this->assertSame(1, $res);

		$res = $this->User->escapeValue(false);
		$this->assertSame(0, $res);

		$res = $this->User->escapeValue(null);
		$this->assertSame(null, $res);

		// comparison to cakes escapeField here (which use ` to escape)
		$res = $this->User->escapeField('dob');
		$this->assertSame('`User`.`dob`', $res);
	}

	/**
	 * MyModelTest::testSaveAll()
	 *
	 * @return void
	 */
	public function testSaveAll() {
		$records = array(
			array('title' => 'x', 'body' => 'bx'),
			array('title' => 'y', 'body' => 'by'),
		);
		$result = $this->User->saveAll($records);
		$this->assertTrue($result);

		$result = $this->User->saveAll($records, array('atomic' => false));
		$this->assertTrue($result);

		$result = $this->User->saveAll($records, array('atomic' => false, 'returnArray' => true));
		$expected = array(true, true);
		$this->assertSame($expected, $result);
	}

	/**
	 * Test deleteAllRaw()
	 *
	 * @return void
	 */
	public function testDeleteAllRaw() {
		$result = $this->User->deleteAllRaw(array('user !=' => 'foo', 'created <' => date(FORMAT_DB_DATE), 'id >' => 1));
		$this->assertTrue($result);
		$result = $this->User->getAffectedRows();
		$this->assertIdentical(3, $result);

		$result = $this->User->deleteAllRaw();
		$this->assertTrue($result);
		$result = $this->User->getAffectedRows();
		$this->assertIdentical(1, $result);
	}

	/**
	 * Test truncate
	 *
	 * @return void
	 */
	public function testTruncate() {
		$is = $this->User->find('count');
		$this->assertEquals(4, $is);

		$is = $this->User->getNextAutoIncrement();
		$this->assertEquals(5, $is);

		$is = $this->User->truncate();
		$is = $this->User->find('count');
		$this->assertEquals(0, $is);

		$is = $this->User->getNextAutoIncrement();
		$this->assertEquals(1, $is);
	}

	/**
	 * Test that 2.x invalidates() can behave like 1.x invalidates()
	 * and that you are able to abort on single errors (similar to using last=>true)
	 *
	 * @return void
	 */
	public function testInvalidates() {
		$TestModel = new AppTestModel();

		$TestModel->validate = array(
			'title' => array(
				'tooShort' => array(
					'rule' => array('minLength', 50),
					'last' => false
				),
				'onlyLetters' => array('rule' => '/^[a-z]+$/i')
			),
		);
		$data = array(
			'title' => 'I am a short string'
		);
		$TestModel->create($data);
		$TestModel->invalidate('title', 'someCustomMessage');

		$result = $TestModel->validates();
		$this->assertFalse($result);

		$result = $TestModel->validationErrors;
		$expected = array(
			'title' => array('someCustomMessage', 'tooShort', 'onlyLetters')
		);
		$this->assertEquals($expected, $result);
		$result = $TestModel->validationErrors;
		$this->assertEquals($expected, $result);

		// invalidate a field with 'last' => true and stop further validation for this field
		$TestModel->create($data);

		$TestModel->invalidate('title', 'someCustomMessage', true);

		$result = $TestModel->validates();
		$this->assertFalse($result);
		$result = $TestModel->validationErrors;
		$expected = array(
			'title' => array('someCustomMessage')
		);
		$this->assertEquals($expected, $result);
		$result = $TestModel->validationErrors;
		$this->assertEquals($expected, $result);
	}

	/**
	 * MyModelTest::testValidateIdentical()
	 *
	 * @return void
	 */
	public function testValidateIdentical() {
		$this->out($this->_header(__FUNCTION__), true);
		$this->User->data = array($this->User->alias => array('y' => 'efg'));
		$is = $this->User->validateIdentical(array('x' => 'efg'), 'y');
		$this->assertTrue($is);

		$this->User->data = array($this->User->alias => array('y' => '2'));
		$is = $this->User->validateIdentical(array('x' => 2), 'y');
		$this->assertFalse($is);

		$this->User->data = array($this->User->alias => array('y' => '3'));
		$is = $this->User->validateIdentical(array('x' => 3), 'y', array('cast' => 'int'));
		$this->assertTrue($is);

		$this->User->data = array($this->User->alias => array('y' => '3'));
		$is = $this->User->validateIdentical(array('x' => 3), 'y', array('cast' => 'string'));
		$this->assertTrue($is);
	}

	/**
	 * MyModelTest::testValidateKey()
	 *
	 * @return void
	 */
	public function testValidateKey() {
		$this->out($this->_header(__FUNCTION__), true);
		//$this->User->data = array($this->User->alias=>array('y'=>'efg'));
		$testModel = new AppTestModel();

		$is = $testModel->validateKey(array('id' => '2'));
		$this->assertFalse($is);

		$is = $testModel->validateKey(array('id' => 2));
		$this->assertFalse($is);

		$is = $testModel->validateKey(array('id' => '4e6f-a2f2-19a4ab957338'));
		$this->assertFalse($is);

		$is = $testModel->validateKey(array('id' => '4dff6725-f0e8-4e6f-a2f2-19a4ab957338'));
		$this->assertTrue($is);

		$is = $testModel->validateKey(array('id' => ''));
		$this->assertFalse($is);

		$is = $testModel->validateKey(array('id' => ''), array('allowEmpty' => true));
		$this->assertTrue($is);

		$is = $testModel->validateKey(array('foreign_id' => '2'));
		$this->assertTrue($is);

		$is = $testModel->validateKey(array('foreign_id' => 2));
		$this->assertTrue($is);

		$is = $testModel->validateKey(array('foreign_id' => 2.3));
		$this->assertFalse($is);

		$is = $testModel->validateKey(array('foreign_id' => -2));
		$this->assertFalse($is);

		$is = $testModel->validateKey(array('foreign_id' => '4dff6725-f0e8-4e6f-a2f2-19a4ab957338'));
		$this->assertFalse($is);

		$is = $testModel->validateKey(array('foreign_id' => 0));
		$this->assertFalse($is);

		$is = $testModel->validateKey(array('foreign_id' => 0), array('allowEmpty' => true));
		$this->assertTrue($is);
	}

	/**
	 * MyModelTest::testValidateEnum()
	 *
	 * @return void
	 */
	public function testValidateEnum() {
		$this->out($this->_header(__FUNCTION__), true);
		//$this->User->data = array($this->User->alias=>array('y'=>'efg'));
		$testModel = new AppTestModel();
		$is = $testModel->validateEnum(array('x' => '1'), true);
		$this->assertTrue($is);

		$is = $testModel->validateEnum(array('x' => '4'), true);
		$this->assertFalse($is);

		$is = $testModel->validateEnum(array('x' => '5'), true, array('4', '5'));
		$this->assertTrue($is);

		$is = $testModel->validateEnum(array('some_key' => '3'), 'x', array('4', '5'));
		$this->assertTrue($is);
	}

	/**
	 * MyModelTest::testGuaranteeFields()
	 *
	 * @return void
	 */
	public function testGuaranteeFields() {
		$this->out($this->_header(__FUNCTION__), true);
		$res = $this->User->guaranteeFields(array());
		//debug($res);
		$this->assertTrue(empty($res));

		$res = $this->User->guaranteeFields(array('x', 'y'));
		//debug($res);
		$this->assertTrue(!empty($res));
		$this->assertEquals($res, array($this->modelName => array('x' => '', 'y' => '')));

		$res = $this->User->guaranteeFields(array('x', 'OtherModel.y'));
		//debug($res);
		$this->assertTrue(!empty($res));
		$this->assertEquals($res, array($this->modelName => array('x' => ''), 'OtherModel' => array('y' => '')));
	}

	/**
	 * MyModelTest::testRequireFields()
	 *
	 * @return void
	 */
	public function testRequireFields() {
		$this->User->requireFields(array('foo', 'bar'));
		$data = array(
			'foo' => 'foo',
		);
		$this->User->set($data);
		$result = $this->User->validates();
		$this->assertFalse($result);

		$data = array(
			'foo' => 'foo',
			'bar' => '',
		);
		$this->User->set($data);
		$result = $this->User->validates();
		$this->assertTrue($result);

		// Allow field to be empty as long as it is present
		$this->User->requireFields(array('foo', 'test'), true);
		$data = array(
			'foo' => 'foo',
			'test' => ''
		);
		$this->User->set($data);
		$result = $this->User->validates();
		$this->assertTrue($result);
	}

	/**
	 * MyModelTest::testSet()
	 *
	 * @return void
	 */
	public function testSet() {
		$this->out($this->_header(__FUNCTION__), true);
		$data = array($this->modelName => array('x' => 'hey'), 'OtherModel' => array('y' => ''));
		$this->User->data = array();

		$res = $this->User->set($data, null, array('x', 'z'));
		$this->out($res);
		$this->assertTrue(!empty($res));
		$this->assertEquals($res, array($this->modelName => array('x' => 'hey', 'z' => ''), 'OtherModel' => array('y' => '')));

		$res = $this->User->data;
		$this->out($res);
		$this->assertTrue(!empty($res));
		$this->assertEquals($res, array($this->modelName => array('x' => 'hey', 'z' => ''), 'OtherModel' => array('y' => '')));
	}

	/**
	 * MyModelTest::testValidateWithGuaranteeFields()
	 *
	 * @return void
	 */
	public function testValidateWithGuaranteeFields() {
		$this->out($this->_header(__FUNCTION__), true);
		$data = array($this->modelName => array('x' => 'hey'), 'OtherModel' => array('y' => ''));

		$data = $this->User->guaranteeFields(array('x', 'z'), $data);
		$this->out($data);
		$this->assertTrue(!empty($data));
		$this->assertEquals(array($this->modelName => array('x' => 'hey', 'z' => ''), 'OtherModel' => array('y' => '')), $data);

		$res = $this->User->set($data);
		$this->out($res);
		$this->assertTrue(!empty($res));
		$this->assertEquals($res, array($this->modelName => array('x' => 'hey', 'z' => ''), 'OtherModel' => array('y' => '')));
	}

	public function testWhitelist() {
		$data = array(
			'name' => 'foo',
			'x' => 'y',
			'z' => 'yes'
		);
		$this->User->set($data);
		$result = $this->User->whitelist(array('name', 'x'));
		$this->assertEquals(array('name', 'x'), array_keys($this->User->data['User']));
	}

	/**
	 * MyModelTest::testBlacklist()
	 * Note that one should always prefer a whitelist over a blacklist.
	 *
	 * @return void
	 */
	public function testBlacklist() {
		$data = array(
			'name' => 'foo',
			'x' => 'y',
			'z' => 'yes'
		);
		$this->User->set($data);
		$this->User->blacklist(array('x'));
		$this->assertEquals(array('name', 'z'), array_keys($this->User->data['User']));
	}

	/**
	 * MyModelTest::testGenerateWhitelistFromBlacklist()
	 *
	 * @return void
	 */
	public function testGenerateWhitelistFromBlacklist() {
		$result = $this->User->generateWhitelistFromBlacklist(array('password'));
		$expected = array('id', 'user', 'created', 'updated');
		$this->assertEquals($expected, array_values($expected));
	}

	/**
	 * MyModelTest::testInvalidate()
	 *
	 * @return void
	 */
	public function testInvalidate() {
		$this->out($this->_header(__FUNCTION__), true);
		$this->User->create();
		$this->User->invalidate('fieldx', __('e %s f', 33));
		$res = $this->User->validationErrors;
		$this->out($res);
		$this->assertTrue(!empty($res));

		$this->User->create();
		$this->User->invalidate('Model.fieldy', __('e %s f %s g', 33, 'xyz'));
		$res = $this->User->validationErrors;
		$this->out($res);
		$this->assertTrue(!empty($res) && $res['Model.fieldy'][0] === 'e 33 f xyz g');

		$this->User->create();
		$this->User->invalidate('fieldy', __('e %s f %s g %s', true, 'xyz', 55));
		$res = $this->User->validationErrors;
		$this->out($res);
		$this->assertTrue(!empty($res) && $res['fieldy'][0] === 'e 1 f xyz g 55');

		$this->User->create();
		$this->User->invalidate('fieldy', array('valErrMandatoryField'));
		$res = $this->User->validationErrors;
		$this->out($res);
		$this->assertTrue(!empty($res));

		$this->User->create();
		$this->User->invalidate('fieldy', 'valErrMandatoryField');
		$res = $this->User->validationErrors;
		$this->out($res);
		$this->assertTrue(!empty($res));

		$this->User->create();
		$this->User->invalidate('fieldy', __('a %s b %s c %s %s %s %s %s h %s', 1, 2, 3, 4, 5, 6, 7, 8));
		$res = $this->User->validationErrors;
		$this->out($res);
		$this->assertTrue(!empty($res) && $res['fieldy'][0] === 'a 1 b 2 c 3 4 5 6 7 h 8');
	}

	/**
	 * MyModelTest::testValidateDate()
	 *
	 * @return void
	 */
	public function testValidateDate() {
		$this->out($this->_header(__FUNCTION__), true);
		$data = array('field' => '2010-01-22');
		$res = $this->User->validateDate($data);
		//debug($res);
		$this->assertTrue($res);

		$data = array('field' => '2010-02-29');
		$res = $this->User->validateDate($data);
		//debug($res);
		$this->assertFalse($res);

		$this->User->data = array($this->User->alias => array('after' => '2010-02-22'));
		$data = array('field' => '2010-02-23 11:11:11');
		$res = $this->User->validateDate($data, array('after' => 'after'));
		//debug($res);
		$this->assertTrue($res);

		$this->User->data = array($this->User->alias => array('after' => '2010-02-24 11:11:11'));
		$data = array('field' => '2010-02-23');
		$res = $this->User->validateDate($data, array('after' => 'after'));
		//debug($res);
		$this->assertFalse($res);

		$this->User->data = array($this->User->alias => array('after' => '2010-02-25'));
		$data = array('field' => '2010-02-25');
		$res = $this->User->validateDate($data, array('after' => 'after'));
		//debug($res);
		$this->assertTrue($res);

		$this->User->data = array($this->User->alias => array('after' => '2010-02-25'));
		$data = array('field' => '2010-02-25');
		$res = $this->User->validateDate($data, array('after' => 'after', 'min' => 1));
		//debug($res);
		$this->assertFalse($res);

		$this->User->data = array($this->User->alias => array('after' => '2010-02-24'));
		$data = array('field' => '2010-02-25');
		$res = $this->User->validateDate($data, array('after' => 'after', 'min' => 2));
		//debug($res);
		$this->assertFalse($res);

		$this->User->data = array($this->User->alias => array('after' => '2010-02-24'));
		$data = array('field' => '2010-02-25');
		$res = $this->User->validateDate($data, array('after' => 'after', 'min' => 1));
		//debug($res);
		$this->assertTrue($res);

		$this->User->data = array($this->User->alias => array('after' => '2010-02-24'));
		$data = array('field' => '2010-02-25');
		$res = $this->User->validateDate($data, array('after' => 'after', 'min' => 2));
		//debug($res);
		$this->assertFalse($res);

		$this->User->data = array($this->User->alias => array('before' => '2010-02-24'));
		$data = array('field' => '2010-02-24');
		$res = $this->User->validateDate($data, array('before' => 'before', 'min' => 1));
		//debug($res);
		$this->assertFalse($res);

		$this->User->data = array($this->User->alias => array('before' => '2010-02-25'));
		$data = array('field' => '2010-02-24');
		$res = $this->User->validateDate($data, array('before' => 'before', 'min' => 1));
		//debug($res);
		$this->assertTrue($res);

		$this->User->data = array($this->User->alias => array('before' => '2010-02-25'));
		$data = array('field' => '2010-02-24');
		$res = $this->User->validateDate($data, array('before' => 'before', 'min' => 2));
		//debug($res);
		$this->assertFalse($res);

		$this->User->data = array($this->User->alias => array('before' => '2010-02-26'));
		$data = array('field' => '2010-02-24');
		$res = $this->User->validateDate($data, array('before' => 'before', 'min' => 2));
		//debug($res);
		$this->assertTrue($res);
	}

	/**
	 * MyModelTest::testValidateDatetime()
	 *
	 * @return void
	 */
	public function testValidateDatetime() {
		$this->out($this->_header(__FUNCTION__), true);
		$data = array('field' => '2010-01-22 11:11:11');
		$res = $this->User->validateDatetime($data);
		//debug($res);
		$this->assertTrue($res);

		$data = array('field' => '2010-01-22 11:61:11');
		$res = $this->User->validateDatetime($data);
		//debug($res);
		$this->assertFalse($res);

		$data = array('field' => '2010-02-29 11:11:11');
		$res = $this->User->validateDatetime($data);
		//debug($res);
		$this->assertFalse($res);

		$data = array('field' => '');
		$res = $this->User->validateDatetime($data, array('allowEmpty' => true));
		//debug($res);
		$this->assertTrue($res);

		$data = array('field' => '0000-00-00 00:00:00');
		$res = $this->User->validateDatetime($data, array('allowEmpty' => true));
		//debug($res);
		$this->assertTrue($res);

		$this->User->data = array($this->User->alias => array('after' => '2010-02-22 11:11:11'));
		$data = array('field' => '2010-02-23 11:11:11');
		$res = $this->User->validateDatetime($data, array('after' => 'after'));
		//debug($res);
		$this->assertTrue($res);

		$this->User->data = array($this->User->alias => array('after' => '2010-02-24 11:11:11'));
		$data = array('field' => '2010-02-23 11:11:11');
		$res = $this->User->validateDatetime($data, array('after' => 'after'));
		//debug($res);
		$this->assertFalse($res);

		$this->User->data = array($this->User->alias => array('after' => '2010-02-23 11:11:11'));
		$data = array('field' => '2010-02-23 11:11:11');
		$res = $this->User->validateDatetime($data, array('after' => 'after'));
		//debug($res);
		$this->assertFalse($res);

		$this->User->data = array($this->User->alias => array('after' => '2010-02-23 11:11:11'));
		$data = array('field' => '2010-02-23 11:11:11');
		$res = $this->User->validateDatetime($data, array('after' => 'after', 'min' => 1));
		//debug($res);
		$this->assertFalse($res);

		$this->User->data = array($this->User->alias => array('after' => '2010-02-23 11:11:11'));
		$data = array('field' => '2010-02-23 11:11:11');
		$res = $this->User->validateDatetime($data, array('after' => 'after', 'min' => 0));
		//debug($res);
		$this->assertTrue($res);

		$this->User->data = array($this->User->alias => array('after' => '2010-02-23 11:11:10'));
		$data = array('field' => '2010-02-23 11:11:11');
		$res = $this->User->validateDatetime($data, array('after' => 'after'));
		//debug($res);
		$this->assertTrue($res);

		$this->User->data = array($this->User->alias => array('after' => '2010-02-23 11:11:12'));
		$data = array('field' => '2010-02-23 11:11:11');
		$res = $this->User->validateDatetime($data, array('after' => 'after'));
		//debug($res);
		$this->assertFalse($res);
	}

	/**
	 * MyModelTest::testValidateTime()
	 *
	 * @return void
	 */
	public function testValidateTime() {
		$this->out($this->_header(__FUNCTION__), true);
		$data = array('field' => '11:21:11');
		$res = $this->User->validateTime($data);
		//debug($res);
		$this->assertTrue($res);

		$data = array('field' => '11:71:11');
		$res = $this->User->validateTime($data);
		//debug($res);
		$this->assertFalse($res);

		$this->User->data = array($this->User->alias => array('before' => '2010-02-23 11:11:12'));
		$data = array('field' => '2010-02-23 11:11:11');
		$res = $this->User->validateTime($data, array('before' => 'before'));
		//debug($res);
		$this->assertTrue($res);

		$this->User->data = array($this->User->alias => array('after' => '2010-02-23 11:11:12'));
		$data = array('field' => '2010-02-23 11:11:11');
		$res = $this->User->validateTime($data, array('after' => 'after'));
		//debug($res);
		$this->assertFalse($res);
	}

	/**
	 * MyModelTest::testValidateUrl()
	 *
	 * @return void
	 */
	public function testValidateUrl() {
		$this->out($this->_header(__FUNCTION__), true);
		$data = array('field' => 'www.dereuromark.de');
		$res = $this->User->validateUrl($data, array('allowEmpty' => true));
		$this->assertTrue($res);

		$data = array('field' => 'www.xxxde');
		$res = $this->User->validateUrl($data, array('allowEmpty' => true));
		$this->assertFalse($res);

		$data = array('field' => 'www.dereuromark.de');
		$res = $this->User->validateUrl($data, array('allowEmpty' => true, 'autoComplete' => false));
		$this->assertFalse($res);

		$data = array('field' => 'http://www.dereuromark.de');
		$res = $this->User->validateUrl($data, array('allowEmpty' => true, 'autoComplete' => false));
		$this->assertTrue($res);

		$data = array('field' => 'www.dereuromark.de');
		$res = $this->User->validateUrl($data, array('strict' => true));
		$this->assertTrue($res); # aha

		$data = array('field' => 'http://www.dereuromark.de');
		$res = $this->User->validateUrl($data, array('strict' => false));
		$this->assertTrue($res);

		$this->skipIf(empty($_SERVER['HTTP_HOST']), 'No HTTP_HOST');

		$data = array('field' => 'http://xyz.de/some/link');
		$res = $this->User->validateUrl($data, array('deep' => false, 'sameDomain' => true));
		$this->assertFalse($res);

		$data = array('field' => '/some/link');
		$res = $this->User->validateUrl($data, array('deep' => false, 'autoComplete' => true));
		$this->assertTrue($_SERVER['HTTP_HOST'] === 'localhost' ? !$res : $res);

		$data = array('field' => 'http://' . $_SERVER['HTTP_HOST'] . '/some/link');
		$res = $this->User->validateUrl($data, array('deep' => false));
		$this->assertTrue($_SERVER['HTTP_HOST'] === 'localhost' ? !$res : $res);

		$data = array('field' => '/some/link');
		$res = $this->User->validateUrl($data, array('deep' => false, 'autoComplete' => false));
		$this->assertTrue((env('REMOTE_ADDR') !== '127.0.0.1') ? !$res : $res);

		//$this->skipIf(strpos($_SERVER['HTTP_HOST'], '.') === false, 'No online HTTP_HOST');

		$data = array('field' => '/some/link');
		$res = $this->User->validateUrl($data, array('deep' => false, 'sameDomain' => true));
		$this->assertTrue($_SERVER['HTTP_HOST'] === 'localhost' ? !$res : $res);

		$data = array('field' => 'https://github.com/');
		$res = $this->User->validateUrl($data, array('deep' => false));
		$this->assertTrue($res);

		$data = array('field' => 'https://github.com/');
		$res = $this->User->validateUrl($data, array('deep' => true));
		$this->assertTrue($res);
	}

	/**
	 * MyModelTest::testValidateUnique()
	 *
	 * @return void
	 */
	public function testValidateUnique() {
		$this->out($this->_header(__FUNCTION__), true);

		$this->Post->validate['title'] = array(
			'validateUnique' => array(
				'rule' => 'validateUnique',
				'message' => 'valErrRecordTitleExists'
			),
		);
		$data = array(
			'title' => 'abc',
			'published' => 'N'
		);
		$this->Post->create($data);
		$res = $this->Post->validates();
		$this->assertTrue($res);
		$res = $this->Post->save($res, false);
		$this->assertTrue((bool)$res);

		$this->Post->create();
		$res = $this->Post->save($data);
		$this->assertFalse($res);

		$this->Post->validate['title'] = array(
			'validateUnique' => array(
				'rule' => array('validateUnique', array('published')),
				'message' => 'valErrRecordTitleExists'
			),
		);
		$data = array(
			'title' => 'abc',
			'published' => 'Y'
		);
		$this->Post->create($data);
		$res = $this->Post->validates();
		$this->assertTrue($res);
		$res = $this->Post->save($res, false);
		$this->assertTrue((bool)$res);

		$this->Post->create();
		$res = $this->Post->save($data);
		$this->assertFalse($res);
	}

}

class MyAppModelPost extends MyModel {

	public $name = 'Post';

	public $alias = 'Post';

	public $belongsTo = 'Author';

}

class MyAppModelUser extends MyModel {

	public $name = 'User';

	public $alias = 'User';

}

class AppTestModel extends MyModel {

	public $useTable = false;

	protected $_schema = array(
		'id' => array (
			'type' => 'string',
			'null' => false,
			'default' => '',
			'length' => 36,
			'key' => 'primary',
			'collate' => 'utf8_unicode_ci',
			'charset' => 'utf8',
		),
		'foreign_id' => array (
			'type' => 'integer',
			'null' => false,
			'default' => '0',
			'length' => 10,
		),
	);

	public static function x() {
		return array('1' => 'x', '2' => 'y', '3' => 'z');
	}

}
