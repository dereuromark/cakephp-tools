<?php

namespace Tools\Model\Behavior;

use Cake\Core\Configure;
use Cake\ORM\TableRegistry;
use Tools\Model\Table\Table;
use Tools\TestSuite\TestCase;

class ResetBehaviorTest extends TestCase {

	/**
	 * @var \Tools\Model\Behavior\ResetBehavior
	 */
	public $ResetBehavior;

	/**
	 * @var \Tools\Model\Table\Table
	 */
	public $Table;

	/**
	 * @var array
	 */
	public $fixtures = ['plugin.tools.reset_comments'];

	public function setUp() {
		parent::setUp();

		Configure::write('App.namespace', 'TestApp');

		//set_time_limit(10);

		$this->Table = TableRegistry::get('ResetComments');
		$this->Table->addBehavior('Tools.Reset');
	}

	public function tearDown() {
		TableRegistry::clear();

		parent::tearDown();
	}

	/**
	 * ResetBehaviorTest::testResetRecords()
	 *
	 * @return void
	 */
	public function testResetRecords() {
		$x = $this->Table->find('all', ['fields' => ['comment'], 'order' => ['updated' => 'DESC']])->first();

		$result = $this->Table->resetRecords();
		$this->assertTrue((bool)$result);

		$y = $this->Table->find('all', ['fields' => ['comment'], 'order' => ['updated' => 'DESC']])->first();
		$this->assertSame($x->toArray(), $y->toArray());
	}

	/**
	 * ResetBehaviorTest::testResetRecordsWithUpdatedTimestamp()
	 *
	 * @return void
	 */
	public function _testResetRecordsWithUpdatedTimestamp() {
		$this->Table->removeBehavior('Reset');
		$this->Table->addBehavior('Tools.Reset', ['updateTimestamp' => true]);

		$x = $this->Table->find('all', ['order' => ['updated' => 'DESC']])->first();
		$this->assertTrue($x['updated'] < '2007-12-31');

		$result = $this->Table->resetRecords();
		$this->assertTrue((bool)$result);

		$x = $this->Table->find('all', ['order' => ['updated' => 'ASC']])->first();
		$this->assertTrue($x['updated'] > (date('Y') - 1) . '-12-31');
	}

	/**
	 * ResetBehaviorTest::testResetWithCallback()
	 *
	 * @return void
	 */
	public function testResetWithCallback() {
		$this->Table->removeBehavior('Reset');
		$this->Table->addBehavior('Tools.Reset', ['callback' => 'customCallback']);

		$x = $this->Table->find('all', ['conditions' => ['id' => 6]])->first();
		$this->assertEquals('Second Comment for Second Article', $x['comment']);

		$result = $this->Table->resetRecords();
		$this->assertTrue((bool)$result);

		$x = $this->Table->find('all', ['conditions' => ['id' => 6]])->first();
		$expected = 'Second Comment for Second Article xyz';
		$this->assertEquals($expected, $x['comment']);
	}

	/**
	 * ResetBehaviorTest::testResetWithObjectCallback()
	 *
	 * @return void
	 */
	public function testResetWithObjectCallback() {
		$this->Table->removeBehavior('Reset');
		$this->Table->addBehavior('Tools.Reset', ['callback' => [$this->Table, 'customObjectCallback']]);

		$x = $this->Table->find('first', ['conditions' => ['id' => 6]]);
		$this->assertEquals('Second Comment for Second Article', $x['comment']);

		$result = $this->Table->resetRecords();
		$this->assertTrue((bool)$result);

		$x = $this->Table->find('first', ['conditions' => ['id' => 6]]);
		$expected = 'Second Comment for Second Article xxx';
		$this->assertEquals($expected, $x['comment']);
	}

	/**
	 * ResetBehaviorTest::testResetWithStaticCallback()
	 *
	 * @return void
	 */
	public function testResetWithStaticCallback() {
		$this->Table->removeBehavior('Reset');
		$this->Table->addBehavior('Tools.Reset', ['callback' => 'TestApp\Model\Table\ResetCommentsTable::customStaticCallback']);

		$x = $this->Table->find('first', ['conditions' => ['id' => 6]]);
		$this->assertEquals('Second Comment for Second Article', $x['comment']);

		$result = $this->Table->resetRecords();
		$this->assertTrue((bool)$result);

		$x = $this->Table->find('first', ['conditions' => ['id' => 6]]);
		$expected = 'Second Comment for Second Article yyy';
		$this->assertEquals($expected, $x['comment']);
	}

	/**
	 * ResetBehaviorTest::testResetWithCallbackAndFields()
	 *
	 * @return void
	 */
	public function testResetWithCallbackAndFields() {
		$this->Table->removeBehavior('Reset');
		$this->Table->addBehavior('Tools.Reset', [
			'fields' => ['id'],
			'updateFields' => ['comment'],
			'callback' => 'TestApp\Model\Table\ResetCommentsTable::fieldsCallback']);

		$x = $this->Table->find('first', ['conditions' => ['id' => 6]]);
		$this->assertEquals('Second Comment for Second Article', $x['comment']);

		$result = $this->Table->resetRecords();
		$this->assertTrue((bool)$result);

		$x = $this->Table->find('first', ['conditions' => ['id' => 6]]);
		$expected = 'foo';
		$this->assertEquals($expected, $x['comment']);
	}

	/**
	 * ResetBehaviorTest::testResetWithCallbackAndFieldsAutoAdded()
	 *
	 * @return void
	 */
	public function testResetWithCallbackAndFieldsAutoAdded() {
		$this->Table->removeBehavior('Reset');
		$this->Table->addBehavior('Tools.Reset', [
			'fields' => ['id'],
			'updateFields' => ['id'],
			'callback' => 'TestApp\Model\Table\ResetCommentsTable::fieldsCallbackAuto']);

		$x = $this->Table->find('first', ['conditions' => ['id' => 6]]);
		$this->assertEquals('Second Comment for Second Article', $x['comment']);

		$result = $this->Table->resetRecords();
		$this->assertTrue((bool)$result);

		$x = $this->Table->find('first', ['conditions' => ['id' => 6]]);
		$expected = 'bar';
		$this->assertEquals($expected, $x['comment']);
	}

}
