<?php

namespace Tools\Test\TestCase\Model\Behavior;

use Cake\Core\Configure;
use Cake\ORM\TableRegistry;
use stdClass;
use Tools\TestSuite\TestCase;

class JsonableBehaviorTest extends TestCase {

	/**
	 * @var array
	 */
	public $fixtures = [
		'plugin.tools.jsonable_comments'
	];

	/**
	 * @var \Tools\Model\Table\Table
	 */
	public $Comments;

	public function setUp() {
		parent::setUp();

		Configure::write('App.namespace', 'TestApp');

		$this->Comments = TableRegistry::get('JsonableComments');
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
	 * JsonableBehaviorTest::testFieldsWithList()
	 *
	 * @return void
	 */
	public function testFieldsWithList() {
		//echo $this->_header(__FUNCTION__);
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
	 * JsonableBehaviorTest::testFieldsWithParam()
	 *
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
	 * JsonableBehaviorTest::testFieldsOnFind()
	 *
	 * @return void
	 */
	public function testFieldsOnFind() {
		//echo $this->_header(__FUNCTION__);

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
	 * JsonableBehaviorTest::testEncodeParams()
	 *
	 * @return void
	 */
	public function testEncodeParams() {
		// $depth param added in 5.5.0
		$this->skipIf(!version_compare(PHP_VERSION, '5.5.0', '>='));

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
		$expected = [];
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

	public function testEncodeParamsAssocFalse() {
		// $depth param added in 5.5.0
		$this->skipIf(!version_compare(PHP_VERSION, '5.5.0', '>='));

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
		$expected = null;
		$this->assertEquals($expected, $res['details']);
	}

	/**
	 * JsonableBehaviorTest::testDecodeParams()
	 *
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

}
