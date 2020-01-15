<?php

namespace Tools\Model\Behavior;

use Cake\ORM\TableRegistry;
use Shim\TestSuite\TestCase;
use TestApp\Model\Table\ResetCommentsTable;
use Tools\Model\Table\Table;

class ResetBehaviorTest extends TestCase {

	/**
	 * @var array
	 */
	protected $fixtures = [
		'plugin.Tools.ResetComments',
	];

	/**
	 * @var \Tools\Model\Behavior\ResetBehavior
	 */
	protected $ResetBehavior;

	/**
	 * @var \Tools\Model\Table\Table
	 */
	protected $Table;

	/**
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		$this->Table = TableRegistry::getTableLocator()->get('ResetComments');
		$this->Table->addBehavior('Tools.Reset');
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
	public function testResetRecords() {
		$x = $this->Table->find('all', ['fields' => ['comment', 'updated'], 'order' => ['updated' => 'DESC']])->first();
		$x['updated'] = (string)$x['updated'];

		$result = $this->Table->resetRecords();
		$this->assertTrue((bool)$result);

		$y = $this->Table->find('all', ['fields' => ['comment', 'updated'], 'order' => ['updated' => 'DESC']])->first();
		$y['updated'] = (string)$y['updated'];
		$this->assertSame($x->toArray(), $y->toArray());
	}

	/**
	 * @return void
	 */
	public function testResetRecordsUpdateField() {
		$this->Table->removeBehavior('Reset');
		$this->Table->addBehavior('Tools.Reset', ['fields' => ['comment'], 'updateFields' => ['comment']]);

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

		$x = $this->Table->find()->where(['id' => 6])->first();
		$this->assertEquals('Second Comment for Second Article', $x['comment']);

		$result = $this->Table->resetRecords();
		$this->assertTrue((bool)$result);

		$x = $this->Table->find()->where(['id' => 6])->first();
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
		$this->Table->addBehavior('Tools.Reset', ['callback' => ResetCommentsTable::class . '::customStaticCallback']);

		$x = $this->Table->find()->where(['id' => 6])->first();
		$this->assertEquals('Second Comment for Second Article', $x['comment']);

		$result = $this->Table->resetRecords();
		$this->assertTrue((bool)$result);

		$x = $this->Table->find()->where(['id' => 6])->first();
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
			'callback' => ResetCommentsTable::class . '::fieldsCallback']);

		$x = $this->Table->find()->where(['id' => 6])->first();
		$this->assertEquals('Second Comment for Second Article', $x['comment']);

		$result = $this->Table->resetRecords();
		$this->assertTrue((bool)$result);

		$x = $this->Table->find()->where(['id' => 6])->first();
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
			'callback' => ResetCommentsTable::class . '::fieldsCallbackAuto']);

		$x = $this->Table->find()->where(['id' => 6])->first();
		$this->assertEquals('Second Comment for Second Article', $x['comment']);

		$result = $this->Table->resetRecords();
		$this->assertTrue((bool)$result);

		$x = $this->Table->find()->where(['id' => 6])->first();
		$expected = 'bar';
		$this->assertEquals($expected, $x['comment']);
	}

}
