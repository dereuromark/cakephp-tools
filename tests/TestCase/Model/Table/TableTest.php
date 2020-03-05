<?php

namespace Tools\Model\Table;

use Cake\Datasource\ConnectionManager;
use Cake\I18n\Time;
use Cake\ORM\TableRegistry;
use Cake\Utility\Hash;
use Shim\TestSuite\TestCase;

class TableTest extends TestCase {

	/**
	 * @var array
	 */
	protected $fixtures = [
		'core.Posts',
		'core.Authors',
		'plugin.Tools.ToolsUsers',
		'plugin.Tools.Roles',
	];

	/**
	 * @var \Tools\Model\Table\Table;
	 */
	protected $Users;

	/**
	 * @var \Tools\Model\Table\Table;
	 */
	protected $Posts;

	/**
	 * SetUp method
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		$this->Users = TableRegistry::getTableLocator()->get('ToolsUsers');

		$this->Posts = TableRegistry::getTableLocator()->get('Posts');
		$this->Posts->belongsTo('Authors');
	}

	/**
	 * @return void
	 */
	public function tearDown(): void {
		TableRegistry::clear();

		parent::tearDown();
	}

	/**
	 * @return void
	 */
	public function testTruncate() {
		$is = $this->Users->find()->count();
		$this->assertEquals(4, $is);

		$config = ConnectionManager::getConfig('test');
		if ((strpos($config['driver'], 'Mysql') !== false)) {
			$is = $this->Users->getNextAutoIncrement();
			$this->assertEquals(5, $is);
		}

		$this->Users->truncate();
		$is = $this->Users->find()->count();
		$this->assertEquals(0, $is);

		if ((strpos($config['driver'], 'Mysql') !== false)) {
			$is = $this->Users->getNextAutoIncrement();
			$this->assertEquals(1, $is);
		}
	}

	/**
	 * @return void
	 */
	public function testTimestamp() {
		$this->Roles = TableRegistry::getTableLocator()->get('Roles');
		$entity = $this->Roles->newEntity(['name' => 'Foo', 'alias' => 'foo']);
		$result = $this->Roles->save($entity);
		$this->assertTrue(!empty($result['created']));
		$this->assertTrue(!empty($result['modified']));
	}

	/**
	 * @return void
	 */
	public function testField() {
		$result = $this->Users->field('name', ['conditions' => ['name' => 'User 1']]);
		$this->assertEquals('User 1', $result);

		$result = $this->Users->field('name', ['conditions' => ['name' => 'User xxx']]);
		$this->assertNull($result);
	}

	/**
	 * @return void
	 */
	public function testFieldByConditions() {
		$result = $this->Users->fieldByConditions('name', ['name' => 'User 1']);
		$this->assertEquals('User 1', $result);

		$result = $this->Users->fieldByConditions('name', ['name' => 'User xxx']);
		$this->assertNull($result);
	}

	/**
	 * Test 2.x shimmed order property
	 *
	 *   $this->order = array('field_name' => 'ASC') etc
	 *
	 * becomes
	 *
	 *   $this->order = array('TableName.field_name' => 'ASC') and a beforeFind addition.
	 *
	 * @return void
	 */
	public function testOrder() {
		$this->Users->truncate();
		$rows = [
			['role_id' => 1, 'name' => 'Gandalf'],
			['role_id' => 2, 'name' => 'Asterix'],
			['role_id' => 1, 'name' => 'Obelix'],
			['role_id' => 3, 'name' => 'Harry Potter']];
		foreach ($rows as $row) {
			$entity = $this->Users->newEntity($row);
			$this->Users->save($entity);
		}

		$result = $this->Users->find('list')->toArray();
		$expected = [
			'Asterix',
			'Gandalf',
			'Harry Potter',
			'Obelix',
		];
		$this->assertSame($expected, array_values($result));
	}

	/**
	 * @return void
	 */
	public function testGetRelatedInUse() {
		$results = $this->Posts->getRelatedInUse('Authors', 'author_id', 'list');
		$expected = [1 => 'mariano', 3 => 'larry'];
		$this->assertEquals($expected, $results->toArray());

		$results = $this->Posts->getRelatedInUse('Authors', null, 'list');
		$expected = [1 => 'mariano', 3 => 'larry'];
		$this->assertEquals($expected, $results->toArray());
	}

	/**
	 * @return void
	 */
	public function testGetFieldInUse() {
		$config = $this->Posts->getConnection()->config();
		$isPostgres = strpos($config['driver'], 'Postgres') !== false;
		$isMysql = strpos($config['driver'], 'Mysql') !== false;
		$this->skipIf($isPostgres || $isMysql, 'Only for MySQL with ONLY_FULL_GROUP_BY disabled right now');

		$results = $this->Posts->getFieldInUse('author_id', 'list')->toArray();
		/*
		$expected = [2 => 'Second Post', 3 => 'Third Post'];
		$this->assertEquals($expected, $results);
		*/
		$this->assertCount(2, $results);

		$results = $this->Posts->getFieldInUse('author_id')->toArray();
		/*
		$expected = ['Second Post', 'Third Post'];
		$this->assertEquals($expected, Hash::extract($results, '{n}.title'));
		*/

		$ids = Hash::extract($results, '{n}.author_id');
		sort($ids);
		$expected = [1, 3];
		$this->assertEquals($expected, $ids);
	}

	/**
	 * TableTest::testValidateDate()
	 *
	 * @return void
	 */
	public function testValidateDate() {
		$date = new Time('2010-01-22');
		$res = $this->Users->validateDate($date);
		$this->assertTrue($res);

		// Careful: now becomes 2010-03-01 in Cake3
		// FIXME
		$date = new Time('2010-02-29');
		$res = $this->Users->validateDate($date);
		$this->assertTrue($res);

		$date = new Time('2010-02-23 11:11:11');
		$context = ['data' => ['after' => new Time('2010-02-22')]];
		$res = $this->Users->validateDate($date, ['after' => 'after'], $context);
		$this->assertTrue($res);

		$date = new Time('2010-02-23');
		$context = ['data' => ['after' => new Time('2010-02-24 11:11:11')]];
		$res = $this->Users->validateDate($date, ['after' => 'after'], $context);
		$this->assertFalse($res);

		$date = new Time('2010-02-25');
		$context = ['data' => ['after' => new Time('2010-02-25')]];
		$res = $this->Users->validateDate($date, ['after' => 'after'], $context);
		$this->assertTrue($res);

		$date = new Time('2010-02-25');
		$context = ['data' => ['after' => new Time('2010-02-25')]];
		$res = $this->Users->validateDate($date, ['after' => 'after', 'min' => 1], $context);
		$this->assertFalse($res);

		$date = new Time('2010-02-25');
		$context = ['data' => ['after' => new Time('2010-02-24')]];
		$res = $this->Users->validateDate($date, ['after' => 'after', 'min' => 2], $context);
		$this->assertFalse($res);

		$date = new Time('2010-02-25');
		$context = ['data' => ['after' => new Time('2010-02-24')]];
		$res = $this->Users->validateDate($date, ['after' => 'after', 'min' => 1], $context);
		$this->assertTrue($res);

		$date = new Time('2010-02-25');
		$context = ['data' => ['after' => new Time('2010-02-24')]];
		$res = $this->Users->validateDate($date, ['after' => 'after', 'min' => 2], $context);
		$this->assertFalse($res);

		$date = new Time('2010-02-24');
		$context = ['data' => ['before' => new Time('2010-02-24')]];
		$res = $this->Users->validateDate($date, ['before' => 'before', 'min' => 1], $context);
		$this->assertFalse($res);

		$date = new Time('2010-02-24');
		$context = ['data' => ['before' => new Time('2010-02-25')]];
		$res = $this->Users->validateDate($date, ['before' => 'before', 'min' => 1], $context);
		$this->assertTrue($res);

		$date = new Time('2010-02-24');
		$context = ['data' => ['before' => new Time('2010-02-25')]];
		$res = $this->Users->validateDate($date, ['before' => 'before', 'min' => 2], $context);
		$this->assertFalse($res);

		$date = new Time('2010-02-24');
		$context = ['data' => ['before' => new Time('2010-02-26')]];
		$res = $this->Users->validateDate($date, ['before' => 'before', 'min' => 2], $context);
		$this->assertTrue($res);
	}

	/**
	 * TableTest::testValidateDatetime()
	 *
	 * @return void
	 */
	public function testValidateDatetime() {
		$date = new Time('2010-01-22 11:11:11');
		$res = $this->Users->validateDatetime($date);
		$this->assertTrue($res);

		/*
		$date = new Time('2010-01-22 11:61:11');
		$res = $this->Users->validateDatetime($date);

		$this->assertFalse($res);
		*/

		//FIXME
		$date = new Time('2010-02-29 11:11:11');
		$res = $this->Users->validateDatetime($date);
		//$this->assertFalse($res);
		$this->assertTrue($res);

		$date = null;
		$res = $this->Users->validateDatetime($date, ['allowEmpty' => true]);
		$this->assertTrue($res);

		/*
		$date = new Time => '0000-00-00 00:00:00');
		$res = $this->Users->validateDatetime($date, array('allowEmpty' => true));

		$this->assertTrue($res);
		*/

		$date = new Time('2010-02-23 11:11:11');
		$context = ['data' => ['after' => new Time('2010-02-22 11:11:11')]];
		$res = $this->Users->validateDatetime($date, ['after' => 'after'], $context);
		$this->assertTrue($res);

		$date = new Time('2010-02-23 11:11:11');
		$context = ['data' => ['after' => new Time('2010-02-24 11:11:11')]];
		$res = $this->Users->validateDatetime($date, ['after' => 'after'], $context);
		$this->assertFalse($res);

		$date = new Time('2010-02-23 11:11:11');
		$context = ['data' => ['after' => new Time('2010-02-23 11:11:11')]];
		$res = $this->Users->validateDatetime($date, ['after' => 'after'], $context);
		$this->assertFalse($res);

		$date = new Time('2010-02-23 11:11:11');
		$context = ['data' => ['after' => new Time('2010-02-23 11:11:11')]];
		$res = $this->Users->validateDatetime($date, ['after' => 'after', 'min' => 1], $context);
		$this->assertFalse($res);

		$date = new Time('2010-02-23 11:11:11');
		$context = ['data' => ['after' => new Time('2010-02-23 11:11:11')]];
		$res = $this->Users->validateDatetime($date, ['after' => 'after', 'min' => 0], $context);
		$this->assertTrue($res);

		$date = new Time('2010-02-23 11:11:11');
		$context = ['data' => ['after' => new Time('2010-02-23 11:11:10')]];
		$res = $this->Users->validateDatetime($date, ['after' => 'after'], $context);
		$this->assertTrue($res);

		$date = new Time('2010-02-23 11:11:11');
		$context = ['data' => ['after' => new Time('2010-02-23 11:11:12')]];
		$res = $this->Users->validateDatetime($date, ['after' => 'after'], $context);
		$this->assertFalse($res);

		$date = new Time('2010-02-24 11:11:11');
		$context = ['data' => ['after' => new Time('2010-02-23 09:11:12')]];
		$res = $this->Users->validateDatetime($date, ['after' => 'after', 'max' => 2 * DAY], $context);
		$this->assertTrue($res);

		$date = new Time('2010-02-24 11:11:11');
		$context = ['data' => ['after' => new Time('2010-02-23 09:11:12')]];
		$res = $this->Users->validateDatetime($date, ['after' => 'after', 'max' => DAY], $context);
		$this->assertFalse($res);

		$date = new Time('2010-02-24 11:11:11');
		$context = ['data' => ['before' => new Time('2010-02-25 13:11:12')]];
		$res = $this->Users->validateDatetime($date, ['before' => 'before', 'max' => 2 * DAY], $context);
		$this->assertTrue($res);

		$date = new Time('2010-02-24 11:11:11');
		$context = ['data' => ['before' => new Time('2010-02-25 13:11:12')]];
		$res = $this->Users->validateDatetime($date, ['before' => 'before', 'max' => DAY], $context);
		$this->assertFalse($res);
	}

	/**
	 * @return void
	 */
	public function testValidateTime() {
		$date = '11:21:11';
		$res = $this->Users->validateTime($date);

		$this->assertTrue($res);

		$date = '11:71:11';
		$res = $this->Users->validateTime($date);

		$this->assertFalse($res);

		$date = '2010-02-23 11:11:11';
		$context = ['data' => ['before' => new Time('2010-02-23 11:11:12')]];
		$res = $this->Users->validateTime($date, ['before' => 'before'], $context);

		$this->assertTrue($res);

		$date = '2010-02-23 11:11:11';
		$context = ['data' => ['after' => new Time('2010-02-23 11:11:12')]];
		$res = $this->Users->validateTime($date, ['after' => 'after'], $context);

		$this->assertFalse($res);
	}

	/**
	 * @return void
	 */
	public function testValidateUrl() {
		$data = 'www.dereuromark.de';
		$res = $this->Users->validateUrl($data, ['allowEmpty' => true]);
		$this->assertTrue($res);

		$data = 'www.xxxde';
		$res = $this->Users->validateUrl($data, ['allowEmpty' => true]);
		$this->assertFalse($res);

		$data = 'www.dereuromark.de';
		$res = $this->Users->validateUrl($data, ['allowEmpty' => true, 'autoComplete' => false]);
		$this->assertFalse($res);

		$data = 'http://www.dereuromark.de';
		$res = $this->Users->validateUrl($data, ['allowEmpty' => true, 'autoComplete' => false]);
		$this->assertTrue($res);

		$data = 'www.dereuromark.de';
		$res = $this->Users->validateUrl($data, ['strict' => true]);
		$this->assertTrue($res); # aha

		$data = 'http://www.dereuromark.de';
		$res = $this->Users->validateUrl($data, ['strict' => false]);
		$this->assertTrue($res);

		$this->skipIf(empty($_SERVER['HTTP_HOST']), 'No HTTP_HOST');

		$data = 'http://xyz.de/some/link';
		$res = $this->Users->validateUrl($data, ['deep' => false, 'sameDomain' => true]);
		$this->assertFalse($res);

		$data = '/some/link';
		$res = $this->Users->validateUrl($data, ['deep' => false, 'autoComplete' => true]);
		$this->assertTrue($_SERVER['HTTP_HOST'] === 'localhost' ? !$res : $res);

		$data = 'http://' . $_SERVER['HTTP_HOST'] . '/some/link';
		$res = $this->Users->validateUrl($data, ['deep' => false]);
		$this->assertTrue($_SERVER['HTTP_HOST'] === 'localhost' ? !$res : $res);

		$data = '/some/link';
		$res = $this->Users->validateUrl($data, ['deep' => false, 'autoComplete' => false]);
		$this->assertTrue((env('REMOTE_ADDR') !== '127.0.0.1') ? !$res : $res);

		//$this->skipIf(strpos($_SERVER['HTTP_HOST'], '.') === false, 'No online HTTP_HOST');

		$data = '/some/link';
		$res = $this->Users->validateUrl($data, ['deep' => false, 'sameDomain' => true]);
		$this->assertTrue($_SERVER['HTTP_HOST'] === 'localhost' ? !$res : $res);

		$data = 'https://github.com/';
		$res = $this->Users->validateUrl($data, ['deep' => false]);
		$this->assertTrue($res);

		$data = 'https://github.com/';
		$res = $this->Users->validateUrl($data, ['deep' => true]);
		$this->assertTrue($res);
	}

	/**
	 * TableTest::testValidateUnique()
	 *
	 * @return void
	 */
	public function testValidateUnique() {
		$this->Posts->getValidator()->add('title', [
			'validateUnique' => [
				'rule' => 'validateUniqueExt',
				'message' => 'valErrRecordTitleExists',
				'provider' => 'table',
			],
		]);
		$data = [
			'title' => 'abc',
			'author_id' => 1,
			'published' => 'N',
		];
		$post = $this->Posts->newEntity($data);
		$this->assertEmpty($post->getErrors());

		$res = $this->Posts->save($post);
		$this->assertTrue((bool)$res);

		$post = $this->Posts->newEntity($data);
		$this->assertNotEmpty($post->getErrors());

		$this->Posts->getValidator()->add('title', [
			'validateUnique' => [
				'rule' => ['validateUniqueExt', ['scope' => ['published']]],
				'message' => 'valErrRecordTitleExists',
				'provider' => 'table',
			],
		]);
		$data = [
			'title' => 'abc',
			'author_id' => 1,
			'published' => 'Y',
		];
		$post = $this->Posts->newEntity($data);
		$this->assertEmpty($post->getErrors());

		$res = $this->Posts->save($post);
		$this->assertTrue((bool)$res);

		$post = $this->Posts->newEntity($data);
		$this->assertNotEmpty($post->getErrors());
	}

}
