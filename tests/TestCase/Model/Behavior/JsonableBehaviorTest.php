<?php

namespace Tools\Test\TestCase\Model\Behavior;

use Cake\ORM\TableRegistry;
use PDOException;
use Shim\TestSuite\TestCase;
use stdClass;

class JsonableBehaviorTest extends TestCase {

	/**
	 * @var array
	 */
	protected $fixtures = [
		'plugin.Tools.JsonableComments',
	];

	/**
	 * @var \Tools\Model\Table\Table
	 */
	protected $Comments;

	/**
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		$this->Comments = TableRegistry::getTableLocator()->get('JsonableComments');
		$this->Comments->addBehavior('Tools.Jsonable', ['fields' => ['details']]);
	}

	/**
	 * JsonableBehaviorTest::testBasic()
	 *
	 * @return void
	 */
	public function testBasic() {
		// accuracy >= 5
		$data = [
			'comment' => 'blabla',
			'url' => 'www.dereuromark.de',
			'title' => 'some Name',
			'details' => ['x' => 'y'],
		];
		$entity = $this->Comments->newEntity($data);
		$res = $this->Comments->save($entity);
		$this->assertTrue((bool)$res);

		$this->assertSame('{"x":"y"}', $res['details']);
	}

	/**
	 * Find list should still work
	 *
	 * @return void
	 */
	public function testList() {
		$data = [
			'comment' => 'blabla',
			'url' => 'www.dereuromark.de',
			'title' => 'some Name',
			'details' => ['x' => 'y'],
		];
		$entity = $this->Comments->newEntity($data);
		$res = $this->Comments->save($entity);
		$this->assertTrue((bool)$res);

		$result = $this->Comments->find('list');
		$expected = [1 => 'some Name'];
		$this->assertSame($expected, $result->toArray());
	}

	/**
	 * @return void
	 */
	public function testFieldsWithList() {
		$this->Comments->removeBehavior('Jsonable');
		$this->Comments->addBehavior('Tools.Jsonable', ['fields' => ['details'], 'input' => 'list']);

		$data = [
			'comment' => 'blabla',
			'url' => 'www.dereuromark.de',
			'title' => 'some Name',
			'details' => 'z|y|x',
		];
		$entity = $this->Comments->newEntity($data);
		$res = $this->Comments->save($entity);
		$this->assertTrue((bool)$res);

		$this->assertSame('["z","y","x"]', $res['details']);

		// with sort and unique
		$data = [
			'comment' => 'blabla',
			'url' => 'www.dereuromark.de',
			'title' => 'some Name',
			'details' => 'z|x|y|x',
		];
		$this->Comments->removeBehavior('Jsonable');
		$this->Comments->addBehavior('Tools.Jsonable', ['fields' => ['details'], 'input' => 'list', 'sort' => true]);

		$entity = $this->Comments->newEntity($data);
		$res = $this->Comments->save($entity);
		$this->assertTrue((bool)$res);

		$this->assertSame('["x","y","z"]', $res['details']);
	}

	/**
	 * @return void
	 */
	public function testFieldsWithParam() {
		$this->Comments->removeBehavior('Jsonable');
		$this->Comments->addBehavior('Tools.Jsonable', ['fields' => ['details'], 'input' => 'param']);

		$data = [
			'comment' => 'blabla',
			'url' => 'www.dereuromark.de',
			'title' => 'some Name',
			'details' => 'z:vz|y:yz|x:xz',
		];
		$entity = $this->Comments->newEntity($data);
		$res = $this->Comments->save($entity);
		$this->assertTrue((bool)$res);

		$this->assertSame('{"z":"vz","y":"yz","x":"xz"}', $res['details']);
	}

	/**
	 * @return void
	 */
	public function testFieldsOnFind() {
		// array
		$this->Comments->removeBehavior('Jsonable');
		$this->Comments->addBehavior('Tools.Jsonable', ['fields' => ['details']]);

		$data = [
			'comment' => 'blabla',
			'url' => 'www.dereuromark.de',
			'title' => 'param',
			'details' => ['x' => 'y'],
		];
		$entity = $this->Comments->newEntity($data);
		$res = $this->Comments->save($entity);
		$this->assertTrue((bool)$res);

		$res = $this->Comments->find('all', ['conditions' => ['title' => 'param']])->first();
		$this->assertEquals(['x' => 'y'], $res['details']);

		// param
		$this->Comments->removeBehavior('Jsonable');

		$res = $this->Comments->find('all', ['conditions' => ['title' => 'param']])->first();
		$this->assertEquals('{"x":"y"}', $res['details']);

		$this->Comments->addBehavior('Tools.Jsonable', ['output' => 'param', 'fields' => ['details']]);

		$res = $this->Comments->find('all', ['conditions' => ['title' => 'param']])->first();
		$this->assertEquals('x:y', $res['details']);

		// list
		$this->Comments->removeBehavior('Jsonable');
		$this->Comments->addBehavior('Tools.Jsonable', ['output' => 'list', 'fields' => ['details']]);

		$data = [
			'comment' => 'blabla',
			'url' => 'www.dereuromark.de',
			'title' => 'list',
			'details' => ['z', 'y', 'x'],
		];
		$entity = $this->Comments->newEntity($data);
		$res = $this->Comments->save($entity);
		$this->assertTrue((bool)$res);

		$this->Comments->removeBehavior('Jsonable');

		$res = $this->Comments->find('all', ['conditions' => ['title' => 'list']])->first();
		$this->assertEquals('["z","y","x"]', $res['details']);

		$this->Comments->addBehavior('Tools.Jsonable', ['output' => 'list', 'fields' => ['details']]);

		$res = $this->Comments->find('all', ['conditions' => ['title' => 'list']])->first();
		$this->assertEquals('z|y|x', $res['details']);

		// custom separator
		$this->Comments->removeBehavior('Jsonable');
		$this->Comments->addBehavior('Tools.Jsonable', ['output' => 'list', 'separator' => ', ', 'fields' => ['details']]);

		// find first
		$res = $this->Comments->find('all', ['conditions' => ['title' => 'list']])->first();
		$this->assertEquals('z, y, x', $res['details']);

		// find all
		$res = $this->Comments->find('all', ['order' => ['title' => 'ASC']])->toArray();
		$this->assertEquals('z, y, x', $res[0]['details']);
	}

	/**
	 * @return void
	 */
	public function testEncodeParams() {
		// Test encode depth = 1
		$this->Comments->removeBehavior('Jsonable');
		$this->Comments->addBehavior('Tools.Jsonable', ['fields' => ['details'], 'encodeParams' => ['depth' => 1]]);

		$data = [
			'comment' => 'blabla',
			'url' => 'www.dereuromark.de',
			'title' => 'param',
			'details' => ['x' => ['y' => 'z']],
		];
		$entity = $this->Comments->newEntity($data);
		$this->Comments->save($entity);

		$res = $this->Comments->find('all', ['conditions' => ['title' => 'param']])->first();
		$expected = ['x' => ['y' => 'z']];
		$this->assertEquals($expected, $res['details']);

		$this->Comments->truncate();

		// Test encode depth = 2
		$this->Comments->removeBehavior('Jsonable');
		$this->Comments->addBehavior('Tools.Jsonable', ['fields' => ['details'], 'encodeParams' => ['depth' => 2], 'decodeParams' => ['assoc' => false]]);

		$data = [
			'comment' => 'blabla',
			'url' => 'www.dereuromark.de',
			'title' => 'param',
			'details' => ['x' => ['y' => 'z']],
		];
		$entity = $this->Comments->newEntity($data);
		$this->Comments->save($entity);

		$res = $this->Comments->find('all', ['conditions' => ['title' => 'param']])->first();
		$obj = new stdClass();
		$obj->x = new stdClass();
		$obj->x->y = 'z';
		$expected = $obj;
		$this->assertEquals($expected, $res['details']);
	}

	/**
	 * @return void
	 */
	public function testEncodeParamsAssocFalse() {
		// Test encode depth = 1
		$this->Comments->removeBehavior('Jsonable');
		$this->Comments->addBehavior('Tools.Jsonable', ['fields' => ['details'], 'encodeParams' => ['depth' => 1], 'decodeParams' => ['assoc' => false]]);

		$data = [
			'comment' => 'blabla',
			'url' => 'www.dereuromark.de',
			'title' => 'param',
			'details' => ['y' => 'yy'],
		];
		$entity = $this->Comments->newEntity($data);
		$this->Comments->save($entity);

		$res = $this->Comments->find('all', ['conditions' => ['title' => 'param']])->first();
		$obj = new stdClass();
		$obj->y = 'yy';
		$expected = $obj;
		$this->assertEquals($expected, $res['details']);

		$this->Comments->truncate();

		$this->Comments->removeBehavior('Jsonable');
		$this->Comments->addBehavior('Tools.Jsonable', ['fields' => ['details'], 'encodeParams' => ['depth' => 1], 'decodeParams' => ['assoc' => false]]);

		$data = [
			'comment' => 'blabla',
			'url' => 'www.dereuromark.de',
			'title' => 'param',
			'details' => ['y' => ['yy' => 'yyy']],
		];
		$entity = $this->Comments->newEntity($data);
		$this->Comments->save($entity);

		$res = $this->Comments->find('all', ['conditions' => ['title' => 'param']])->first();
		$expected = new stdClass();
		$expected->y = new stdClass();
		$expected->y->yy = 'yyy';
		$this->assertEquals($expected, $res['details']);
	}

	/**
	 * @return void
	 */
	public function testDecodeParams() {
		$this->Comments->removeBehavior('Jsonable');
		$this->Comments->addBehavior('Tools.Jsonable', ['output' => 'array', 'fields' => ['details'], 'decodeParams' => ['assoc' => false]]);

		$data = [
			'comment' => 'blabla',
			'url' => 'www.dereuromark.de',
			'title' => 'param',
			'details' => ['x' => ['y' => 'z']],
		];
		$entity = $this->Comments->newEntity($data);
		$this->Comments->save($entity);

		// Test decode with default params
		$res = $this->Comments->find('all', ['conditions' => ['title' => 'param']])->first();
		$obj = new stdClass();
		$obj->x = new stdClass();
		$obj->x->y = 'z';
		$expected = $obj;
		$this->assertEquals($expected, $res['details']);

		// Test decode with assoc = true
		$this->Comments->removeBehavior('Jsonable');
		$this->Comments->addBehavior('Tools.Jsonable', ['fields' => ['details'], 'decodeParams' => ['assoc' => true]]);

		$res = $this->Comments->find('all', ['conditions' => ['title' => 'param']])->first();
		$expected = ['x' => ['y' => 'z']];
		$this->assertEquals($expected, $res['details']);

		// Test decode with assoc = true and depth = 2
		$this->Comments->removeBehavior('Jsonable');
		$this->Comments->addBehavior('Tools.Jsonable', ['fields' => ['details'], 'decodeParams' => ['assoc' => true, 'depth' => 2]]);

		$res = $this->Comments->find('all', ['conditions' => ['title' => 'param']])->first();
		$expected = [];
		$this->assertEquals($expected, $res['details']);

		// Test decode with assoc = true and depth = 3
		$this->Comments->removeBehavior('Jsonable');
		$this->Comments->addBehavior('Tools.Jsonable', ['fields' => ['details'], 'decodeParams' => ['assoc' => true, 'depth' => 3]]);

		$res = $this->Comments->find('all', ['conditions' => ['title' => 'param']])->first();
		$expected = ['x' => ['y' => 'z']];
		$this->assertEquals($expected, $res['details']);
	}

	/**
	 * @return void
	 */
	public function testEncodeWithComplexContent() {
		$this->Comments->removeBehavior('Jsonable');
		$this->Comments->addBehavior('Tools.Jsonable', [
			'output' => 'array',
			'fields' => ['details'],
		]);

		$data = [
			'comment' => 'blabla',
			'url' => 'www.dereuromark.de',
			'title' => 'param',
			'details' => [
				'foo' => 'bar',
				'nan' => NAN,
				'inf' => INF,
			],
		];
		$entity = $this->Comments->newEntity($data);
		$result = $this->Comments->save($entity);
		$this->assertTrue((bool)$result);

		$res = $this->Comments->get($entity->id);
		$expected = [
			'foo' => 'bar',
			'nan' => 0,
			'inf' => 0,
		];
		$this->assertSame($expected, $res->details);
	}

	/**
	 * @return void
	 */
	public function testEncodeWithNoParamsComplexContent() {
		$this->Comments->removeBehavior('Jsonable');
		$this->Comments->addBehavior('Tools.Jsonable', [
			'output' => 'array',
			'fields' => ['details'],
			'encodeParams' => [
				'options' => 0,
			],
		]);

		$data = [
			'comment' => 'blabla',
			'url' => 'www.dereuromark.de',
			'title' => 'param',
			'details' => [
				'foo' => 'bar',
				'nan' => NAN,
				'inf' => INF,
			],
		];
		$entity = $this->Comments->newEntity($data);

		$this->expectException(PDOException::class);

		$this->Comments->save($entity);
	}

	/**
	 * @return void
	 */
	public function testEncodeWithNoParamsComplexContentNullable() {
		$this->Comments->removeBehavior('Jsonable');
		$this->Comments->addBehavior('Tools.Jsonable', [
			'output' => 'array',
			'fields' => ['details_nullable', 'details'],
			'encodeParams' => [
				'options' => 0,
			],
		]);

		$data = [
			'comment' => 'blabla',
			'url' => 'www.dereuromark.de',
			'title' => 'param',
			'details' => [
			],
			'details_nullable' => [
				'foo' => 'bar',
				'nan' => NAN,
				'inf' => INF,
			],
		];
		$entity = $this->Comments->newEntity($data);
		$result = $this->Comments->save($entity);
		$this->assertTrue((bool)$result);

		$res = $this->Comments->get($entity->id);
		$this->assertSame([], $res->details);
		$this->assertNull($res->details_nullable);
	}

}
