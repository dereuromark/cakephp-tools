<?php

namespace Tools\Test\TestCase\Model\Behavior;

use RuntimeException;
use Shim\TestSuite\TestCase;
use TestApp\Model\Entity\BitmaskedComment;
use TestApp\Model\Enum\BitmaskEnum;

class BitmaskedBehaviorTest extends TestCase {

	/**
	 * @var array
	 */
	protected array $fixtures = [
		'plugin.Tools.BitmaskedComments',
	];

	/**
	 * @var \Tools\Model\Table\Table|\Tools\Model\Behavior\BitmaskedBehavior
	 */
	protected $Comments;

	/**
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		$this->Comments = $this->getTableLocator()->get('BitmaskedComments');
	}

	/**
	 * @return void
	 */
	public function testConfig() {
		$this->Comments->addBehavior('Tools.Bitmasked', []);
		$bits = $this->Comments->behaviors()->Bitmasked->getConfig('bits');
		$expected = BitmaskedComment::statuses();
		$this->assertSame($expected, $bits);
	}

	/**
	 * @return void
	 */
	public function testFieldMethodMissing() {
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('Bits not found for field my_field, expected pluralized static method myFields() on the entity.');

		$this->Comments->addBehavior('Tools.Bitmasked', ['field' => 'my_field']);
	}

	/**
	 * @return void
	 */
	public function testEncodeBitmask() {
		$this->Comments->addBehavior('Tools.Bitmasked', ['mappedField' => 'statuses']);

		$res = $this->Comments->encodeBitmask([BitmaskedComment::STATUS_PUBLISHED, BitmaskedComment::STATUS_APPROVED]);
		$expected = BitmaskedComment::STATUS_PUBLISHED | BitmaskedComment::STATUS_APPROVED;
		$this->assertSame($expected, $res);
	}

	/**
	 * @return void
	 */
	public function testDecodeBitmask() {
		$this->Comments->addBehavior('Tools.Bitmasked', ['mappedField' => 'statuses']);

		$res = $this->Comments->decodeBitmask(BitmaskedComment::STATUS_PUBLISHED | BitmaskedComment::STATUS_APPROVED);
		$expected = [BitmaskedComment::STATUS_PUBLISHED, BitmaskedComment::STATUS_APPROVED];
		$this->assertSame($expected, $res);
	}

	/**
	 * @return void
	 */
	public function testFind() {
		$this->Comments->addBehavior('Tools.Bitmasked', ['mappedField' => 'statuses']);

		$res = $this->Comments->find('all')->toArray();
		$this->assertTrue(!empty($res) && is_array($res));

		$this->assertTrue(!empty($res[1]['statuses']) && is_array($res[1]['statuses']));
	}

	/**
	 * @return void
	 */
	public function testFindBitmasked() {
		$this->Comments->addBehavior('Tools.Bitmasked', ['mappedField' => 'statuses']);

		$res = $this->Comments->find('bits', bits: [])->toArray();
		$this->assertCount(1, $res);
		$this->assertSame([], $res[0]->statuses);

		$res = $this->Comments->find('bits', bits: [BitmaskedComment::STATUS_ACTIVE, BitmaskedComment::STATUS_APPROVED])->toArray();
		$this->assertCount(1, $res);
		$this->assertSame([BitmaskedComment::STATUS_ACTIVE, BitmaskedComment::STATUS_APPROVED], $res[0]->statuses);
	}

	/**
	 * @return void
	 */
	public function testFindBitmaskedEnums() {
		$this->Comments->addBehavior('Tools.Bitmasked', ['bits' => BitmaskEnum::class, 'mappedField' => 'statuses']);

		$res = $this->Comments->find('bits', bits: [])->toArray();
		$this->assertCount(1, $res);
		$this->assertSame([], $res[0]->statuses);

		$res = $this->Comments->find('bits', bits: [BitmaskEnum::One, BitmaskEnum::Four])->toArray();
		$this->assertCount(1, $res);
		$this->assertSame([BitmaskEnum::One, BitmaskEnum::Four], $res[0]->statuses);
	}

	/**
	 * @return void
	 */
	public function testFindBitmaskedContain() {
		$this->Comments->addBehavior('Tools.Bitmasked', ['mappedField' => 'statuses']);

		$bits = [];
		$options = [
			'type' => 'contain',
		];
		$res = $this->Comments->find('bits', bits: $bits, options: $options)->toArray();
		$this->assertCount(1, $res);
		$this->assertSame([], $res[0]->statuses);

		$bits = [BitmaskedComment::STATUS_APPROVED];
		$options = [
			'type' => 'contain',
		];
		$res = $this->Comments->find('bits', bits: $bits, options: $options)->toArray();
		$this->assertCount(3, $res);

		$bits = [BitmaskedComment::STATUS_APPROVED, BitmaskedComment::STATUS_PUBLISHED];
		$options = [
			'type' => 'contain',
		];
		$res = $this->Comments->find('bits', bits: $bits, options: $options)->toArray();
		$this->assertCount(5, $res);
	}

	/**
	 * @return void
	 */
	public function testFindBitmaskedContainAnd() {
		$this->Comments->addBehavior('Tools.Bitmasked', ['mappedField' => 'statuses']);

		$bits = [];
		$options = [
			'type' => 'contain',
			'containMode' => 'and',
		];
		$res = $this->Comments->find('bits', bits: $bits, options: $options)->toArray();
		$this->assertCount(1, $res);
		$this->assertSame([], $res[0]->statuses);

		$bits = [BitmaskedComment::STATUS_APPROVED];
		$res = $this->Comments->find('bits', bits: $bits, options: $options)->toArray();
		$this->assertCount(3, $res);

		$bits = [BitmaskedComment::STATUS_APPROVED, BitmaskedComment::STATUS_PUBLISHED];
		$res = $this->Comments->find('bits', bits: $bits, options: $options)->toArray();
		$this->assertCount(1, $res);
	}

	/**
	 * @return void
	 */
	public function testSaveBasic() {
		$this->Comments->addBehavior('Tools.Bitmasked', ['mappedField' => 'statuses']);

		$data = [
			'comment' => 'test save',
			'statuses' => [],
		];

		$entity = $this->Comments->newEntity($data);
		$res = $this->Comments->save($entity);
		$this->assertTrue((bool)$res);
		$this->assertSame(0, $entity->get('status'));

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
		$this->assertEmpty($entity->getErrors());

		$res = $this->Comments->save($entity);
		$this->assertTrue((bool)$res);

		$res = $this->Comments->find()->where(['statuses IN' => $data['statuses']])->first();
		$this->assertTrue(!empty($res));
		$expected = BitmaskedComment::STATUS_APPROVED | BitmaskedComment::STATUS_PUBLISHED; // 6
		$this->assertEquals($expected, $res['status']);
		$expected = $data['statuses'];

		$this->assertEquals($expected, $res['statuses']);

		// model.field syntax
		$res = $this->Comments->find()->where(['BitmaskedComments.statuses IN' => $data['statuses']])->first();
		$this->assertTrue((bool)$res);

		// explicit
		$activeApprovedAndPublished = BitmaskedComment::STATUS_ACTIVE | BitmaskedComment::STATUS_APPROVED | BitmaskedComment::STATUS_PUBLISHED;
		$data = [
			'comment' => 'another post comment',
			'status' => $activeApprovedAndPublished,
		];
		$entity = $this->Comments->newEntity($data);
		$res = $this->Comments->save($entity);
		$this->assertTrue((bool)$res);

		$res = $this->Comments->find()->where(['status' => $activeApprovedAndPublished])->first();
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
		$this->Comments->addBehavior('Tools.Bitmasked', ['mappedField' => 'statuses']);

		$data = [
			'comment' => 'test save',
			'statuses' => [],
		];
		$entity = $this->Comments->newEntity($data);
		$res = $this->Comments->save($entity);
		$this->assertTrue((bool)$res);
		$this->assertSame(0, $entity->get('status'));

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
		$this->Comments->addBehavior('Tools.Bitmasked', ['mappedField' => 'statuses', 'on' => 'beforeSave']);
		$data = [
			'comment' => 'test save',
			'statuses' => [BitmaskedComment::STATUS_PUBLISHED, BitmaskedComment::STATUS_APPROVED],
		];
		$entity = $this->Comments->newEntity($data);
		$this->assertEmpty($entity->getErrors());

		$res = $this->Comments->save($entity);
		$this->assertTrue((bool)$res);
		$this->assertSame(BitmaskedComment::STATUS_PUBLISHED | BitmaskedComment::STATUS_APPROVED, $res['status']);
	}

	/**
	 * @return void
	 */
	public function testIs() {
		$this->Comments->addBehavior('Tools.Bitmasked', ['mappedField' => 'statuses']);

		$res = $this->Comments->isBit(BitmaskedComment::STATUS_PUBLISHED);
		$expected = ['BitmaskedComments.status' => 2];
		$this->assertEquals($expected, $res);
	}

	/**
	 * @return void
	 */
	public function testIsNot() {
		$this->Comments->addBehavior('Tools.Bitmasked', ['mappedField' => 'statuses']);

		$res = $this->Comments->isNotBit(BitmaskedComment::STATUS_PUBLISHED);
		$expected = ['NOT' => ['BitmaskedComments.status' => 2]];
		$this->assertEquals($expected, $res);
	}

	/**
	 * @return void
	 */
	public function testContains() {
		$this->Comments->addBehavior('Tools.Bitmasked', ['mappedField' => 'statuses']);

		$config = $this->Comments->getConnection()->config();
		$isPostgres = strpos($config['driver'], 'Postgres') !== false;

		$res = $this->Comments->containsBit(BitmaskedComment::STATUS_PUBLISHED);
		$expected = ['(BitmaskedComments.status & 2 = 2)'];
		if ($isPostgres) {
			$expected = ['("BitmaskedComments"."status" & 2 = 2)'];
		}
		$this->assertEquals($expected, $res);

		$conditions = $res;
		$res = $this->Comments->find('all', ...['conditions' => $conditions])->toArray();
		$this->assertTrue(!empty($res) && count($res) === 3);

		// multiple (AND)
		$res = $this->Comments->containsBit([BitmaskedComment::STATUS_PUBLISHED, BitmaskedComment::STATUS_ACTIVE]);
		$expected = ['(BitmaskedComments.status & 3 = 3)'];
		if ($isPostgres) {
			$expected = ['("BitmaskedComments"."status" & 3 = 3)'];
		}
		$this->assertEquals($expected, $res);

		$conditions = $res;
		$res = $this->Comments->find('all', ...['conditions' => $conditions])->toArray();
		$this->assertTrue(!empty($res) && count($res) === 2);
	}

	/**
	 * @return void
	 */
	public function testNotContains() {
		$this->Comments->addBehavior('Tools.Bitmasked', ['mappedField' => 'statuses']);

		$config = $this->Comments->getConnection()->config();
		$isPostgres = strpos($config['driver'], 'Postgres') !== false;

		$res = $this->Comments->containsNotBit(BitmaskedComment::STATUS_PUBLISHED);
		$expected = ['(BitmaskedComments.status & 2 != 2)'];
		if ($isPostgres) {
			$expected = ['("BitmaskedComments"."status" & 2 != 2)'];
		}
		$this->assertEquals($expected, $res);

		$conditions = $res;
		$res = $this->Comments->find('all', ...['conditions' => $conditions])->toArray();
		$this->assertTrue(!empty($res) && count($res) === 4);

		// multiple (AND)
		$res = $this->Comments->containsNotBit([BitmaskedComment::STATUS_PUBLISHED, BitmaskedComment::STATUS_ACTIVE]);
		$expected = ['(BitmaskedComments.status & 3 != 3)'];
		if ($isPostgres) {
			$expected = ['("BitmaskedComments"."status" & 3 != 3)'];
		}
		$this->assertEquals($expected, $res);

		$conditions = $res;
		$res = $this->Comments->find('all', ...['conditions' => $conditions])->toArray();
		$this->assertTrue(!empty($res) && count($res) === 5);
	}

}
