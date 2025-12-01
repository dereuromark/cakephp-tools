<?php

namespace Tools\Test\TestCase\Model\Table;

use Cake\I18n\Time;
use Cake\Utility\Hash;
use DateTime as NativeDateTime;
use DateTimeImmutable;
use Shim\TestSuite\TestCase;
use Tools\I18n\Date;
use Tools\I18n\DateTime;

class TableTest extends TestCase {

	/**
	 * @var array
	 */
	protected array $fixtures = [
		'plugin.Tools.Posts',
		'plugin.Tools.Authors',
		'plugin.Tools.ToolsUsers',
		'plugin.Tools.Roles',
	];

	/**
	 * @var \Tools\Model\Table\Table
	 */
	protected $Users;

	/**
	 * @var \Tools\Model\Table\Table
	 */
	protected $Posts;

	/**
	 * SetUp method
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		$this->Users = $this->getTableLocator()->get('ToolsUsers');

		$this->Posts = $this->getTableLocator()->get('Posts');
		$this->Posts->belongsTo('Authors');
	}

	/**
	 * @return void
	 */
	public function tearDown(): void {
		$this->getTableLocator()->clear();

		parent::tearDown();
	}

	/**
	 * @return void
	 */
	public function testTruncate() {
		$is = $this->Users->find()->count();
		$this->assertSame(4, $is);

		$this->Users->truncate();
		$is = $this->Users->find()->count();
		$this->assertSame(0, $is);
	}

	/**
	 * @return void
	 */
	public function testTimestamp() {
		$Roles = $this->getTableLocator()->get('Roles');
		$entity = $Roles->newEntity(['name' => 'Foo', 'alias' => 'foo']);
		$result = $Roles->save($entity);
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
			['role_id' => 3, 'name' => 'Harry Potter'],
		];
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
		$date = new Date('2010-01-22');
		$res = $this->Users->validateDate($date);
		$this->assertTrue($res);

		// Careful: now becomes 2010-03-01 in Cake3
		// FIXME
		$date = new DateTime('2010-02-29');
		$res = $this->Users->validateDate($date);
		$this->assertTrue($res);

		$date = new DateTime('2010-02-23 11:11:11');
		$context = ['data' => ['after' => new DateTime('2010-02-22')]];
		$res = $this->Users->validateDate($date, ['after' => 'after'], $context);
		$this->assertTrue($res);

		$date = new DateTime('2010-02-23');
		$context = ['data' => ['after' => new DateTime('2010-02-24 11:11:11')]];
		$res = $this->Users->validateDate($date, ['after' => 'after'], $context);
		$this->assertFalse($res);

		$date = new DateTime('2010-02-25');
		$context = ['data' => ['after' => new DateTime('2010-02-25')]];
		$res = $this->Users->validateDate($date, ['after' => 'after'], $context);
		$this->assertTrue($res);

		$date = new DateTime('2010-02-25');
		$context = ['data' => ['after' => new DateTime('2010-02-25')]];
		$res = $this->Users->validateDate($date, ['after' => 'after', 'min' => 1], $context);
		$this->assertFalse($res);

		$date = new DateTime('2010-02-25');
		$context = ['data' => ['after' => new DateTime('2010-02-24')]];
		$res = $this->Users->validateDate($date, ['after' => 'after', 'min' => 2], $context);
		$this->assertFalse($res);

		$date = new DateTime('2010-02-25');
		$context = ['data' => ['after' => new DateTime('2010-02-24')]];
		$res = $this->Users->validateDate($date, ['after' => 'after', 'min' => 1], $context);
		$this->assertTrue($res);

		$date = new DateTime('2010-02-25');
		$context = ['data' => ['after' => new DateTime('2010-02-24')]];
		$res = $this->Users->validateDate($date, ['after' => 'after', 'min' => 2], $context);
		$this->assertFalse($res);

		$date = new DateTime('2010-02-24');
		$context = ['data' => ['before' => new DateTime('2010-02-24')]];
		$res = $this->Users->validateDate($date, ['before' => 'before', 'min' => 1], $context);
		$this->assertFalse($res);

		$date = new DateTime('2010-02-24');
		$context = ['data' => ['before' => new DateTime('2010-02-25')]];
		$res = $this->Users->validateDate($date, ['before' => 'before', 'min' => 1], $context);
		$this->assertTrue($res);

		$date = new DateTime('2010-02-24');
		$context = ['data' => ['before' => new DateTime('2010-02-25')]];
		$res = $this->Users->validateDate($date, ['before' => 'before', 'min' => 2], $context);
		$this->assertFalse($res);

		$date = new DateTime('2010-02-24');
		$context = ['data' => ['before' => new DateTime('2010-02-26')]];
		$res = $this->Users->validateDate($date, ['before' => 'before', 'min' => 2], $context);
		$this->assertTrue($res);

		$date = new Date('2010-02-24');
		$context = ['data' => ['before' => new Date('2010-02-26')]];
		$res = $this->Users->validateDate($date, ['before' => 'before', 'min' => 2], $context);
		$this->assertTrue($res);

		$date = ['year' => 2010, 'month' => 2, 'day' => 24];
		$context = ['data' => ['before' => ['year' => 2010, 'month' => 2, 'day' => 26]]];
		$res = $this->Users->validateDate($date, ['before' => 'before', 'min' => 2], $context);
		$this->assertTrue($res);

		$date = ['year' => 2010, 'month' => 2, 'day' => 24];
		$context = ['data' => ['before' => ['year' => 2010, 'month' => 2, 'day' => 20]]];
		$res = $this->Users->validateDate($date, ['before' => 'before', 'min' => 2], $context);
		$this->assertFalse($res);

		$date = ['year' => 2010, 'month' => 2, 'day' => 24];
		$context = ['data' => ['before' => ['year' => '', 'month' => '', 'day' => '']]];
		$res = $this->Users->validateDate($date, ['before' => 'before', 'min' => 2], $context);
		$this->assertTrue($res);

		// Test 'after' with object directly (not field reference)
		$date = new DateTime('2010-02-25');
		$res = $this->Users->validateDate($date, ['after' => new DateTime('2010-02-24')]);
		$this->assertTrue($res);

		$date = new Date('2010-02-25');
		$res = $this->Users->validateDate($date, ['after' => new Date('2010-02-24')]);
		$this->assertTrue($res);

		$date = new DateTime('2010-02-25');
		$res = $this->Users->validateDate($date, ['after' => new DateTime('2010-02-26')]);
		$this->assertFalse($res);

		$date = new Date('2010-02-25');
		$res = $this->Users->validateDate($date, ['after' => new Date('2010-02-25')]);
		$this->assertTrue($res);

		$date = new DateTime('2010-02-25');
		$res = $this->Users->validateDate($date, ['after' => new DateTime('2010-02-25'), 'min' => 1]);
		$this->assertFalse($res);

		$date = new Date('2010-02-26');
		$res = $this->Users->validateDate($date, ['after' => new Date('2010-02-24'), 'min' => 2]);
		$this->assertTrue($res);

		$date = new DateTime('2010-02-25');
		$res = $this->Users->validateDate($date, ['after' => new DateTime('2010-02-24'), 'min' => 2]);
		$this->assertFalse($res);

		// Test 'before' with object directly (not field reference)
		$date = new DateTime('2010-02-24');
		$res = $this->Users->validateDate($date, ['before' => new DateTime('2010-02-25')]);
		$this->assertTrue($res);

		$date = new Date('2010-02-24');
		$res = $this->Users->validateDate($date, ['before' => new Date('2010-02-25')]);
		$this->assertTrue($res);

		$date = new DateTime('2010-02-25');
		$res = $this->Users->validateDate($date, ['before' => new DateTime('2010-02-24')]);
		$this->assertFalse($res);

		$date = new Date('2010-02-25');
		$res = $this->Users->validateDate($date, ['before' => new Date('2010-02-25')]);
		$this->assertTrue($res);

		$date = new DateTime('2010-02-24');
		$res = $this->Users->validateDate($date, ['before' => new DateTime('2010-02-24'), 'min' => 1]);
		$this->assertFalse($res);

		$date = new Date('2010-02-24');
		$res = $this->Users->validateDate($date, ['before' => new Date('2010-02-26'), 'min' => 2]);
		$this->assertTrue($res);

		$date = new DateTime('2010-02-24');
		$res = $this->Users->validateDate($date, ['before' => new DateTime('2010-02-25'), 'min' => 2]);
		$this->assertFalse($res);

		// Test DateTimeInterface objects (e.g., DateTimeImmutable)
		$date = new DateTime('2010-02-25');
		$after = new DateTimeImmutable('2010-02-24');
		$res = $this->Users->validateDate($date, ['after' => $after]);
		$this->assertTrue($res);

		$date = new Date('2010-02-24');
		$before = new DateTimeImmutable('2010-02-25');
		$res = $this->Users->validateDate($date, ['before' => $before]);
		$this->assertTrue($res);

		// Test combinations of after and before objects
		$date = new DateTime('2010-02-25');
		$res = $this->Users->validateDate($date, [
			'after' => new DateTime('2010-02-24'),
			'before' => new DateTime('2010-02-26'),
		]);
		$this->assertTrue($res);

		$date = new Date('2010-02-25');
		$res = $this->Users->validateDate($date, [
			'after' => new Date('2010-02-24'),
			'before' => new Date('2010-02-26'),
		]);
		$this->assertTrue($res);

		$date = new DateTime('2010-02-25');
		$res = $this->Users->validateDate($date, [
			'after' => new DateTime('2010-02-26'),
			'before' => new DateTime('2010-02-27'),
		]);
		$this->assertFalse($res);

		$date = new DateTime('2010-02-25');
		$res = $this->Users->validateDate($date, [
			'after' => new DateTime('2010-02-23'),
			'before' => new DateTime('2010-02-24'),
		]);
		$this->assertFalse($res);

		// Test min days with object combinations
		$date = new DateTime('2010-02-25');
		$res = $this->Users->validateDate($date, [
			'after' => new DateTime('2010-02-24'),
			'before' => new DateTime('2010-02-27'),
			'min' => 1,
		]);
		$this->assertTrue($res);

		$date = new Date('2010-02-25');
		$res = $this->Users->validateDate($date, [
			'after' => new Date('2010-02-24'),
			'before' => new Date('2010-02-26'),
			'min' => 1,
		]);
		$this->assertTrue($res);

		$date = new Date('2010-02-25');
		$res = $this->Users->validateDate($date, [
			'after' => new Date('2010-02-25'),
			'before' => new Date('2010-02-26'),
			'min' => 1,
		]);
		$this->assertFalse($res);
	}

	/**
	 * TableTest::testValidateDatetime()
	 *
	 * @return void
	 */
	public function testValidateDatetime() {
		$date = new DateTime('2010-01-22 11:11:11');
		$res = $this->Users->validateDatetime($date);
		$this->assertTrue($res);

		/*
		$date = new Time('2010-01-22 11:61:11');
		$res = $this->Users->validateDatetime($date);

		$this->assertFalse($res);
		*/

		//FIXME
		$date = new DateTime('2010-02-29 11:11:11');
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

		$date = new DateTime('2010-02-23 11:11:11');
		$context = ['data' => ['after' => new DateTime('2010-02-22 11:11:11')]];
		$res = $this->Users->validateDatetime($date, ['after' => 'after'], $context);
		$this->assertTrue($res);

		$date = new DateTime('2010-02-23 11:11:11');
		$context = ['data' => ['after' => new DateTime('2010-02-24 11:11:11')]];
		$res = $this->Users->validateDatetime($date, ['after' => 'after'], $context);
		$this->assertFalse($res);

		$date = new DateTime('2010-02-23 11:11:11');
		$context = ['data' => ['after' => new DateTime('2010-02-23 11:11:11')]];
		$res = $this->Users->validateDatetime($date, ['after' => 'after'], $context);
		$this->assertFalse($res);

		$date = new DateTime('2010-02-23 11:11:11');
		$context = ['data' => ['after' => new DateTime('2010-02-23 11:11:11')]];
		$res = $this->Users->validateDatetime($date, ['after' => 'after', 'min' => 1], $context);
		$this->assertFalse($res);

		$date = new DateTime('2010-02-23 11:11:11');
		$context = ['data' => ['after' => new DateTime('2010-02-23 11:11:11')]];
		$res = $this->Users->validateDatetime($date, ['after' => 'after', 'min' => 0], $context);
		$this->assertTrue($res);

		$date = new DateTime('2010-02-23 11:11:11');
		$context = ['data' => ['after' => new DateTime('2010-02-23 11:11:10')]];
		$res = $this->Users->validateDatetime($date, ['after' => 'after'], $context);
		$this->assertTrue($res);

		$date = new DateTime('2010-02-23 11:11:11');
		$context = ['data' => ['after' => new DateTime('2010-02-23 11:11:12')]];
		$res = $this->Users->validateDatetime($date, ['after' => 'after'], $context);
		$this->assertFalse($res);

		$date = new DateTime('2010-02-24 11:11:11');
		$context = ['data' => ['after' => new DateTime('2010-02-23 09:11:12')]];
		$res = $this->Users->validateDatetime($date, ['after' => 'after', 'max' => 2 * DAY], $context);
		$this->assertTrue($res);

		$date = new DateTime('2010-02-24 11:11:11');
		$context = ['data' => ['after' => new DateTime('2010-02-23 09:11:12')]];
		$res = $this->Users->validateDatetime($date, ['after' => 'after', 'max' => DAY], $context);
		$this->assertFalse($res);

		$date = new DateTime('2010-02-24 11:11:11');
		$context = ['data' => ['before' => new DateTime('2010-02-25 13:11:12')]];
		$res = $this->Users->validateDatetime($date, ['before' => 'before', 'max' => 2 * DAY], $context);
		$this->assertTrue($res);

		$date = new DateTime('2010-02-24 11:11:11');
		$context = ['data' => ['before' => new DateTime('2010-02-25 13:11:12')]];
		$res = $this->Users->validateDatetime($date, ['before' => 'before', 'max' => DAY], $context);
		$this->assertFalse($res);

		// Test 'after' with object directly (not field reference)
		$date = new DateTime('2010-02-25 14:30:00');
		$res = $this->Users->validateDatetime($date, ['after' => new DateTime('2010-02-25 14:29:59')]);
		$this->assertTrue($res);

		$date = new DateTime('2010-02-25 14:30:00');
		$res = $this->Users->validateDatetime($date, ['after' => new DateTime('2010-02-25 14:30:01')]);
		$this->assertFalse($res);

		$date = new DateTime('2010-02-25 14:30:00');
		$res = $this->Users->validateDatetime($date, ['after' => new DateTime('2010-02-25 14:30:00')]);
		$this->assertFalse($res);

		$date = new DateTime('2010-02-25 14:30:00');
		$res = $this->Users->validateDatetime($date, ['after' => new DateTime('2010-02-25 14:30:00'), 'min' => 0]);
		$this->assertTrue($res);

		$date = new DateTime('2010-02-25 14:30:10');
		$res = $this->Users->validateDatetime($date, ['after' => new DateTime('2010-02-25 14:30:00'), 'min' => 10]);
		$this->assertTrue($res);

		$date = new DateTime('2010-02-25 14:30:09');
		$res = $this->Users->validateDatetime($date, ['after' => new DateTime('2010-02-25 14:30:00'), 'min' => 10]);
		$this->assertFalse($res);

		// Test 'after' with max option
		$date = new DateTime('2010-02-26 14:30:00');
		$res = $this->Users->validateDatetime($date, ['after' => new DateTime('2010-02-25 14:30:00'), 'max' => 2 * DAY]);
		$this->assertTrue($res);

		$date = new DateTime('2010-02-28 14:30:00');
		$res = $this->Users->validateDatetime($date, ['after' => new DateTime('2010-02-25 14:30:00'), 'max' => 2 * DAY]);
		$this->assertFalse($res);

		// Test 'before' with object directly (not field reference)
		$date = new DateTime('2010-02-25 14:29:59');
		$res = $this->Users->validateDatetime($date, ['before' => new DateTime('2010-02-25 14:30:00')]);
		$this->assertTrue($res);

		$date = new DateTime('2010-02-25 14:30:01');
		$res = $this->Users->validateDatetime($date, ['before' => new DateTime('2010-02-25 14:30:00')]);
		$this->assertFalse($res);

		$date = new DateTime('2010-02-25 14:30:00');
		$res = $this->Users->validateDatetime($date, ['before' => new DateTime('2010-02-25 14:30:00')]);
		$this->assertFalse($res);

		$date = new DateTime('2010-02-25 14:30:00');
		$res = $this->Users->validateDatetime($date, ['before' => new DateTime('2010-02-25 14:30:00'), 'min' => 0]);
		$this->assertTrue($res);

		$date = new DateTime('2010-02-25 14:29:50');
		$res = $this->Users->validateDatetime($date, ['before' => new DateTime('2010-02-25 14:30:00'), 'min' => 10]);
		$this->assertTrue($res);

		$date = new DateTime('2010-02-25 14:29:51');
		$res = $this->Users->validateDatetime($date, ['before' => new DateTime('2010-02-25 14:30:00'), 'min' => 10]);
		$this->assertFalse($res);

		// Test 'before' with max option
		$date = new DateTime('2010-02-25 14:30:00');
		$res = $this->Users->validateDatetime($date, ['before' => new DateTime('2010-02-26 14:30:00'), 'max' => 2 * DAY]);
		$this->assertTrue($res);

		$date = new DateTime('2010-02-24 14:30:00');
		$res = $this->Users->validateDatetime($date, ['before' => new DateTime('2010-02-27 14:30:00'), 'max' => 2 * DAY]);
		$this->assertFalse($res);

		// Test DateTimeInterface objects (e.g., DateTimeImmutable)
		$date = new DateTime('2010-02-25 14:30:00');
		$after = new DateTimeImmutable('2010-02-25 14:29:00');
		$res = $this->Users->validateDatetime($date, ['after' => $after]);
		$this->assertTrue($res);

		$date = new DateTime('2010-02-25 14:30:00');
		$before = new DateTimeImmutable('2010-02-25 14:31:00');
		$res = $this->Users->validateDatetime($date, ['before' => $before]);
		$this->assertTrue($res);

		// Test combinations of after and before objects
		$date = new DateTime('2010-02-25 14:30:00');
		$res = $this->Users->validateDatetime($date, [
			'after' => new DateTime('2010-02-25 14:29:00'),
			'before' => new DateTime('2010-02-25 14:31:00'),
		]);
		$this->assertTrue($res);

		$date = new DateTime('2010-02-25 14:30:00');
		$res = $this->Users->validateDatetime($date, [
			'after' => new DateTime('2010-02-25 14:31:00'),
			'before' => new DateTime('2010-02-25 14:32:00'),
		]);
		$this->assertFalse($res);

		$date = new DateTime('2010-02-25 14:30:00');
		$res = $this->Users->validateDatetime($date, [
			'after' => new DateTime('2010-02-25 14:28:00'),
			'before' => new DateTime('2010-02-25 14:29:00'),
		]);
		$this->assertFalse($res);

		// Test min seconds with object combinations
		$date = new DateTime('2010-02-25 14:30:00');
		$res = $this->Users->validateDatetime($date, [
			'after' => new DateTime('2010-02-25 14:29:00'),
			'before' => new DateTime('2010-02-25 14:31:00'),
			'min' => 60,
		]);
		$this->assertTrue($res);

		$date = new DateTime('2010-02-25 14:30:00');
		$res = $this->Users->validateDatetime($date, [
			'after' => new DateTime('2010-02-25 14:29:30'),
			'before' => new DateTime('2010-02-25 14:30:30'),
			'min' => 30,
		]);
		$this->assertTrue($res);

		$date = new DateTime('2010-02-25 14:30:00');
		$res = $this->Users->validateDatetime($date, [
			'after' => new DateTime('2010-02-25 14:29:31'),
			'before' => new DateTime('2010-02-25 14:30:29'),
			'min' => 30,
		]);
		$this->assertFalse($res);

		// Test max with object combinations
		$date = new DateTime('2010-02-26 14:30:00');
		$res = $this->Users->validateDatetime($date, [
			'after' => new DateTime('2010-02-25 14:30:00'),
			'before' => new DateTime('2010-02-28 14:30:00'),
			'max' => 2 * DAY,
		]);
		$this->assertTrue($res);

		$date = new DateTime('2010-02-28 14:30:00');
		$res = $this->Users->validateDatetime($date, [
			'after' => new DateTime('2010-02-25 14:30:00'),
			'before' => new DateTime('2010-03-02 14:30:00'),
			'max' => 2 * DAY,
		]);
		$this->assertFalse($res);
	}

	/**
	 * @return void
	 */
	public function testValidateTime() {
		$time = '11:21:11';
		$res = $this->Users->validateTime($time);
		$this->assertTrue($res);

		$time = '11:71:11';
		$res = $this->Users->validateTime($time);
		$this->assertFalse($res);

		// Test with allowEmpty
		$time = null;
		$res = $this->Users->validateTime($time, ['allowEmpty' => true]);
		$this->assertTrue($res);

		$time = null;
		$res = $this->Users->validateTime($time);
		$this->assertFalse($res);

		// Test extracting time from datetime string
		$time = '2010-02-23 11:11:11';
		$res = $this->Users->validateTime($time);
		$this->assertTrue($res);

		// Test with field reference (existing behavior)
		$time = '2010-02-23 11:11:11';
		$context = ['data' => ['before' => new NativeDateTime('2010-02-23 11:11:12')]];
		$res = $this->Users->validateTime($time, ['before' => 'before'], $context);
		$this->assertTrue($res);

		$time = '2010-02-23 11:11:11';
		$context = ['data' => ['after' => new NativeDateTime('2010-02-23 11:11:12')]];
		$res = $this->Users->validateTime($time, ['after' => 'after'], $context);
		$this->assertFalse($res);

		// Test 'after' with Time object directly
		$time = '14:30:00';
		$res = $this->Users->validateTime($time, ['after' => new Time('14:29:59')]);
		$this->assertTrue($res);

		$time = '14:30:00';
		$res = $this->Users->validateTime($time, ['after' => new Time('14:30:01')]);
		$this->assertFalse($res);

		$time = '14:30:00';
		$res = $this->Users->validateTime($time, ['after' => new Time('14:30:00')]);
		$this->assertFalse($res);

		$time = '14:30:00';
		$res = $this->Users->validateTime($time, ['after' => new Time('14:30:00'), 'min' => 0]);
		$this->assertTrue($res);

		// Test 'after' with min seconds
		$time = '14:30:10';
		$res = $this->Users->validateTime($time, ['after' => new Time('14:30:00'), 'min' => 10]);
		$this->assertTrue($res);

		$time = '14:30:09';
		$res = $this->Users->validateTime($time, ['after' => new Time('14:30:00'), 'min' => 10]);
		$this->assertFalse($res);

		// Test 'after' with max seconds
		$time = '14:30:00';
		$res = $this->Users->validateTime($time, ['after' => new Time('14:29:00'), 'max' => 120]);
		$this->assertTrue($res);

		$time = '14:31:01';
		$res = $this->Users->validateTime($time, ['after' => new Time('14:29:00'), 'max' => 120]);
		$this->assertFalse($res);

		// Test 'before' with Time object directly
		$time = '14:29:59';
		$res = $this->Users->validateTime($time, ['before' => new Time('14:30:00')]);
		$this->assertTrue($res);

		$time = '14:30:01';
		$res = $this->Users->validateTime($time, ['before' => new Time('14:30:00')]);
		$this->assertFalse($res);

		$time = '14:30:00';
		$res = $this->Users->validateTime($time, ['before' => new Time('14:30:00')]);
		$this->assertFalse($res);

		$time = '14:30:00';
		$res = $this->Users->validateTime($time, ['before' => new Time('14:30:00'), 'min' => 0]);
		$this->assertTrue($res);

		// Test 'before' with min seconds
		$time = '14:29:50';
		$res = $this->Users->validateTime($time, ['before' => new Time('14:30:00'), 'min' => 10]);
		$this->assertTrue($res);

		$time = '14:29:51';
		$res = $this->Users->validateTime($time, ['before' => new Time('14:30:00'), 'min' => 10]);
		$this->assertFalse($res);

		// Test 'before' with max seconds
		$time = '14:30:00';
		$res = $this->Users->validateTime($time, ['before' => new Time('14:32:00'), 'max' => 120]);
		$this->assertTrue($res);

		$time = '14:29:59';
		$res = $this->Users->validateTime($time, ['before' => new Time('14:32:01'), 'max' => 120]);
		$this->assertFalse($res);

		// Test with string time values for after/before
		$time = '14:30:00';
		$res = $this->Users->validateTime($time, ['after' => '14:29:00']);
		$this->assertTrue($res);

		$time = '14:30:00';
		$res = $this->Users->validateTime($time, ['before' => '14:31:00']);
		$this->assertTrue($res);

		// Test with datetime strings for after/before
		$time = '14:30:00';
		$res = $this->Users->validateTime($time, ['after' => '2010-02-23 14:29:00']);
		$this->assertTrue($res);

		$time = '14:30:00';
		$res = $this->Users->validateTime($time, ['before' => '2010-02-23 14:31:00']);
		$this->assertTrue($res);

		// Test with DateTime objects for after/before
		$time = '14:30:00';
		$res = $this->Users->validateTime($time, ['after' => new DateTime('2010-02-23 14:29:00')]);
		$this->assertTrue($res);

		$time = '14:30:00';
		$res = $this->Users->validateTime($time, ['before' => new DateTime('2010-02-23 14:31:00')]);
		$this->assertTrue($res);

		// Test combinations of after and before
		$time = '14:30:00';
		$res = $this->Users->validateTime($time, [
			'after' => new Time('14:29:00'),
			'before' => new Time('14:31:00'),
		]);
		$this->assertTrue($res);

		$time = '14:30:00';
		$res = $this->Users->validateTime($time, [
			'after' => new Time('14:31:00'),
			'before' => new Time('14:32:00'),
		]);
		$this->assertFalse($res);

		$time = '14:30:00';
		$res = $this->Users->validateTime($time, [
			'after' => new Time('14:28:00'),
			'before' => new Time('14:29:00'),
		]);
		$this->assertFalse($res);

		// Test min with combinations
		$time = '14:30:00';
		$res = $this->Users->validateTime($time, [
			'after' => new Time('14:29:00'),
			'before' => new Time('14:31:00'),
			'min' => 60,
		]);
		$this->assertTrue($res);

		$time = '14:30:00';
		$res = $this->Users->validateTime($time, [
			'after' => new Time('14:29:30'),
			'before' => new Time('14:30:30'),
			'min' => 30,
		]);
		$this->assertTrue($res);

		$time = '14:30:00';
		$res = $this->Users->validateTime($time, [
			'after' => new Time('14:29:31'),
			'before' => new Time('14:30:29'),
			'min' => 30,
		]);
		$this->assertFalse($res);

		// Test max with combinations
		$time = '14:30:00';
		$res = $this->Users->validateTime($time, [
			'after' => new Time('14:29:00'),
			'before' => new Time('14:32:00'),
			'max' => 120,
		]);
		$this->assertTrue($res);

		$time = '14:30:00';
		$res = $this->Users->validateTime($time, [
			'after' => new Time('14:28:00'),
			'before' => new Time('14:33:00'),
			'max' => 120,
		]);
		$this->assertFalse($res);

		// Test Time input value
		$time = new Time('14:30:00');
		$res = $this->Users->validateTime($time, ['after' => new Time('14:29:00')]);
		$this->assertTrue($res);

		// Test DateTime input value (extracts time portion)
		$time = new DateTime('2010-02-23 14:30:00');
		$res = $this->Users->validateTime($time, ['after' => new Time('14:29:00')]);
		$this->assertTrue($res);

		// Test midnight wrapping scenarios
		$time = '23:59:59';
		$res = $this->Users->validateTime($time, ['after' => new Time('23:59:58')]);
		$this->assertTrue($res);

		$time = '00:00:01';
		$res = $this->Users->validateTime($time, ['after' => new Time('00:00:00')]);
		$this->assertTrue($res);

		$time = '00:00:00';
		$res = $this->Users->validateTime($time, ['before' => new Time('00:00:01')]);
		$this->assertTrue($res);
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

		$data = 'https://www.dereuromark.de';
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

		$this->Posts->getValidator()->remove('title');
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
