<?php

namespace Tools\Test\TestCase\Model\Behavior;

use Cake\Core\Configure;
use Cake\ORM\TableRegistry;
use TestApp\Model\Entity\BitmaskedComment;
use Tools\TestSuite\TestCase;

class BitmaskedBehaviorTest extends TestCase {

	/**
	 * @var array
	 */
	public $fixtures = [
		'plugin.tools.bitmasked_comments'
	];

	/**
	 * @var \Tools\Model\Table\Table
	 */
	public $Comments;

	public function setUp() {
		parent::setUp();

		Configure::write('App.namespace', 'TestApp');

		$this->Comments = TableRegistry::get('BitmaskedComments');
		$this->Comments->addBehavior('Tools.Bitmasked', ['mappedField' => 'statuses']);
	}

	/**
	 * BitmaskedBehaviorTest::testEncodeBitmask()
	 *
	 * @return void
	 */
	public function testEncodeBitmask() {
		$res = $this->Comments->encodeBitmask([BitmaskedComment::STATUS_PUBLISHED, BitmaskedComment::STATUS_APPROVED]);
		$expected = BitmaskedComment::STATUS_PUBLISHED | BitmaskedComment::STATUS_APPROVED;
		$this->assertSame($expected, $res);
	}

	/**
	 * BitmaskedBehaviorTest::testDecodeBitmask()
	 *
	 * @return void
	 */
	public function testDecodeBitmask() {
		$res = $this->Comments->decodeBitmask(BitmaskedComment::STATUS_PUBLISHED | BitmaskedComment::STATUS_APPROVED);
		$expected = [BitmaskedComment::STATUS_PUBLISHED, BitmaskedComment::STATUS_APPROVED];
		$this->assertSame($expected, $res);
	}

	/**
	 * BitmaskedBehaviorTest::testFind()
	 *
	 * @return void
	 */
	public function testFind() {
		$res = $this->Comments->find('all')->toArray();
		$this->assertTrue(!empty($res) && is_array($res));

		$this->assertTrue(!empty($res[1]['statuses']) && is_array($res[1]['statuses']));
	}

	/**
	 * BitmaskedBehaviorTest::testSave()
	 *
	 * @return void
	 */
	public function testSaveBasic() {
		$data = [
			'comment' => 'test save',
			'statuses' => [],
		];

		$entity = $this->Comments->newEntity($data);
		$res = $this->Comments->save($entity);
		$this->assertTrue((bool)$res);
		$this->assertSame('0', $entity->get('status'));

		$data = [
			'comment' => 'test save',
			'statuses' => [BitmaskedComment::STATUS_PUBLISHED, BitmaskedComment::STATUS_APPROVED],
		];
		$entity = $this->Comments->newEntity($data);
		$res = $this->Comments->save($entity);
		$this->assertTrue((bool)$res);

		$is = $entity->get('status');
		$this->assertSame(BitmaskedComment::STATUS_PUBLISHED | BitmaskedComment::STATUS_APPROVED, $is);

		// save + find
		$entity = $this->Comments->newEntity($data);
		$this->assertEmpty($entity->errors());

		$res = $this->Comments->save($entity);
		$this->assertTrue((bool)$res);

		$res = $this->Comments->find('first', ['conditions' => ['statuses' => $data['statuses']]]);
		$this->assertTrue(!empty($res));
		$expected = BitmaskedComment::STATUS_APPROVED | BitmaskedComment::STATUS_PUBLISHED; // 6
		$this->assertEquals($expected, $res['status']);
		$expected = $data['statuses'];

		$this->assertEquals($expected, $res['statuses']);

		// model.field syntax
		$res = $this->Comments->find('first', ['conditions' => ['BitmaskedComments.statuses' => $data['statuses']]]);
		$this->assertTrue((bool)$res->toArray());

		// explicit
		$activeApprovedAndPublished = BitmaskedComment::STATUS_ACTIVE | BitmaskedComment::STATUS_APPROVED | BitmaskedComment::STATUS_PUBLISHED;
		$data = [
			'comment' => 'another post comment',
			'status' => $activeApprovedAndPublished,
		];
		$entity = $this->Comments->newEntity($data);
		$res = $this->Comments->save($entity);
		$this->assertTrue((bool)$res);

		$res = $this->Comments->find('first', ['conditions' => ['status' => $activeApprovedAndPublished]]);
		$this->assertTrue((bool)$res);
		$this->assertEquals($activeApprovedAndPublished, $res['status']);
		$expected = [BitmaskedComment::STATUS_ACTIVE, BitmaskedComment::STATUS_PUBLISHED, BitmaskedComment::STATUS_APPROVED];

		$this->assertEquals($expected, $res['statuses']);
	}

	/**
	 * Assert that you can manually trigger "notEmpty" rule with null instead of 0 for "not null" db fields
	 *
	 * @return void
	 */
	public function testSaveWithDefaultValue() {
		$data = [
			'comment' => 'test save',
			'statuses' => [],
		];
		$entity = $this->Comments->newEntity($data);
		$res = $this->Comments->save($entity);
		$this->assertTrue((bool)$res);
		$this->assertSame('0', $entity->get('status'));

		$this->skipIf(true, '//FIXME');

		// Now let's set the default value
		$this->Comments->removeBehavior('Bitmasked');
		$this->Comments->addBehavior('Tools.Bitmasked', ['mappedField' => 'statuses', 'defaultValue' => '']);
		$data = [
			'comment' => 'test save',
			'statuses' => [],
		];
		$entity = $this->Comments->newEntity($data);
		$res = $this->Comments->save($entity);
		$this->assertFalse($res);

		$this->assertSame('', $entity->get('status'));
	}

	/**
	 * Assert that it also works with beforeSave event callback.
	 *
	 * @return void
	 */
	public function testSaveOnBeforeSave() {
		$this->Comments->removeBehavior('Bitmasked');
		$this->Comments->addBehavior('Tools.Bitmasked', ['mappedField' => 'statuses', 'on' => 'beforeSave']);
		$data = [
			'comment' => 'test save',
			'statuses' => [BitmaskedComment::STATUS_PUBLISHED, BitmaskedComment::STATUS_APPROVED],
		];
		$entity = $this->Comments->newEntity($data);
		$this->assertEmpty($entity->errors());

		$res = $this->Comments->save($entity);
		$this->assertTrue((bool)$res);
		$this->assertSame(BitmaskedComment::STATUS_PUBLISHED | BitmaskedComment::STATUS_APPROVED, $res['status']);
	}

	/**
	 * BitmaskedBehaviorTest::testIs()
	 *
	 * @return void
	 */
	public function testIs() {
		$res = $this->Comments->isBit(BitmaskedComment::STATUS_PUBLISHED);
		$expected = ['BitmaskedComments.status' => 2];
		$this->assertEquals($expected, $res);
	}

	/**
	 * BitmaskedBehaviorTest::testIsNot()
	 *
	 * @return void
	 */
	public function testIsNot() {
		$res = $this->Comments->isNotBit(BitmaskedComment::STATUS_PUBLISHED);
		$expected = ['NOT' => ['BitmaskedComments.status' => 2]];
		$this->assertEquals($expected, $res);
	}

	/**
	 * BitmaskedBehaviorTest::testContains()
	 *
	 * @return void
	 */
	public function testContains() {
		$res = $this->Comments->containsBit(BitmaskedComment::STATUS_PUBLISHED);
		$expected = ['(BitmaskedComments.status & 2 = 2)'];
		$this->assertEquals($expected, $res);

		$conditions = $res;
		$res = $this->Comments->find('all', ['conditions' => $conditions])->toArray();
		$this->assertTrue(!empty($res) && count($res) === 3);

		// multiple (AND)
		$res = $this->Comments->containsBit([BitmaskedComment::STATUS_PUBLISHED, BitmaskedComment::STATUS_ACTIVE]);

		$expected = ['(BitmaskedComments.status & 3 = 3)'];
		$this->assertEquals($expected, $res);

		$conditions = $res;
		$res = $this->Comments->find('all', ['conditions' => $conditions])->toArray();
		$this->assertTrue(!empty($res) && count($res) === 2);
	}

	/**
	 * BitmaskedBehaviorTest::testNotContains()
	 *
	 * @return void
	 */
	public function testNotContains() {
		$res = $this->Comments->containsNotBit(BitmaskedComment::STATUS_PUBLISHED);
		$expected = ['(BitmaskedComments.status & 2 != 2)'];
		$this->assertEquals($expected, $res);

		$conditions = $res;
		$res = $this->Comments->find('all', ['conditions' => $conditions])->toArray();
		$this->assertTrue(!empty($res) && count($res) === 4);

		// multiple (AND)
		$res = $this->Comments->containsNotBit([BitmaskedComment::STATUS_PUBLISHED, BitmaskedComment::STATUS_ACTIVE]);

		$expected = ['(BitmaskedComments.status & 3 != 3)'];
		$this->assertEquals($expected, $res);

		$conditions = $res;
		$res = $this->Comments->find('all', ['conditions' => $conditions])->toArray();
		$this->assertTrue(!empty($res) && count($res) === 5);
	}

}
