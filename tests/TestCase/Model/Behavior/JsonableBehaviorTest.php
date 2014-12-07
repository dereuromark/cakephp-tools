<?php

namespace Tools\Test\TestCase\Model\Behavior;

use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use Cake\Core\Configure;
use Tools\Model\Behavior\JsonableBehavior;

class JsonableBehaviorTest extends TestCase {

	public $fixtures = array(
		'plugin.tools.jsonable_comments'
	);

	public $Comments;

	public function setUp() {
		parent::setUp();

		$this->Comments = TableRegistry::get('JsonableComments');
		$this->Comments->addBehavior('Tools.Jsonable', array('fields' => array('details')));
	}

	/**
	 * JsonableBehaviorTest::testBasic()
	 *
	 * @return void
	 */
	public function testBasic() {
		// accuracy >= 5
		$data = array(
			'comment' => 'blabla',
			'url' => 'www.dereuromark.de',
			'title' => 'some Name',
			'details' => array('x' => 'y'),
		);
		$entity = $this->Comments->newEntity($data);
		$res = $this->Comments->save($entity);
		$this->assertTrue((bool)$res);

		$this->assertSame('{"x":"y"}', $res['details']);
	}

	/**
	 * JsonableBehaviorTest::testFieldsWithList()
	 *
	 * @return void
	 */
	public function testFieldsWithList() {
		//echo $this->_header(__FUNCTION__);
		$this->Comments->removeBehavior('Jsonable');
		$this->Comments->addBehavior('Tools.Jsonable', array('fields' => array('details'), 'input' => 'list'));

		$data = array(
			'comment' => 'blabla',
			'url' => 'www.dereuromark.de',
			'title' => 'some Name',
			'details' => 'z|y|x',
		);
		$entity = $this->Comments->newEntity($data);
		$res = $this->Comments->save($entity);
		$this->assertTrue((bool)$res);

		$this->assertSame('["z","y","x"]', $res['details']);

		// with sort and unique
		$data = array(
			'comment' => 'blabla',
			'url' => 'www.dereuromark.de',
			'title' => 'some Name',
			'details' => 'z|x|y|x',
		);
		$this->Comments->removeBehavior('Jsonable');
		$this->Comments->addBehavior('Tools.Jsonable', array('fields' => array('details'), 'input' => 'list', 'sort' => true));

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
		$this->Comments->addBehavior('Tools.Jsonable', array('fields' => array('details'), 'input' => 'param'));

		$data = array(
			'comment' => 'blabla',
			'url' => 'www.dereuromark.de',
			'title' => 'some Name',
			'details' => 'z:vz|y:yz|x:xz',
		);
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
		$this->Comments->addBehavior('Tools.Jsonable', array('fields' => array('details')));

		$data = array(
			'comment' => 'blabla',
			'url' => 'www.dereuromark.de',
			'title' => 'param',
			'details' => array('x' => 'y'),
		);
		$entity = $this->Comments->newEntity($data);
		$res = $this->Comments->save($entity);
		$this->assertTrue((bool)$res);

		$res = $this->Comments->find('all', array('conditions' => array('title' => 'param')))->first();
		$this->assertEquals(array('x' => 'y'), $res['details']);

		// param
		$this->Comments->removeBehavior('Jsonable');

		$res = $this->Comments->find('all', array('conditions' => array('title' => 'param')))->first();
		$this->assertEquals('{"x":"y"}', $res['details']);

		$this->Comments->addBehavior('Tools.Jsonable', array('output' => 'param', 'fields' => array('details')));

		$res = $this->Comments->find('all', array('conditions' => array('title' => 'param')))->first();
		$this->assertEquals('x:y', $res['details']);

		// list
		$this->Comments->removeBehavior('Jsonable');
		$this->Comments->addBehavior('Tools.Jsonable', array('output' => 'list', 'fields' => array('details')));

		$data = array(
			'comment' => 'blabla',
			'url' => 'www.dereuromark.de',
			'title' => 'list',
			'details' => array('z', 'y', 'x'),
		);
		$entity = $this->Comments->newEntity($data);
		$res = $this->Comments->save($entity);
		$this->assertTrue((bool)$res);

		$this->Comments->removeBehavior('Jsonable');

		$res = $this->Comments->find('all', array('conditions' => array('title' => 'list')))->first();
		$this->assertEquals('["z","y","x"]', $res['details']);

		$this->Comments->addBehavior('Tools.Jsonable', array('output' => 'list', 'fields' => array('details')));

		$res = $this->Comments->find('all', array('conditions' => array('title' => 'list')))->first();
		$this->assertEquals('z|y|x', $res['details']);

		// custom separator
		$this->Comments->removeBehavior('Jsonable');
		$this->Comments->addBehavior('Tools.Jsonable', array('output' => 'list', 'separator' => ', ', 'fields' => array('details')));

		// find first
		$res = $this->Comments->find('all', array('conditions' => array('title' => 'list')))->first();
		$this->assertEquals('z, y, x', $res['details']);

		// find all
		$res = $this->Comments->find('all', array('order' => array('title' => 'ASC')))->toArray();
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
		$this->Comments->addBehavior('Tools.Jsonable', array('fields' => array('details'), 'encodeParams' => array('depth' => 1)));

		$data = array(
			'comment' => 'blabla',
			'url' => 'www.dereuromark.de',
			'title' => 'param',
			'details' => array('x' => array('y' => 'z')),
		);
		$entity = $this->Comments->newEntity($data);
		$this->Comments->save($entity);

		$res = $this->Comments->find('all', array('conditions' => array('title' => 'param')))->first();
		$expected = array();
		$this->assertEquals($expected, $res['details']);

		// Test encode depth = 2
		$this->Comments->removeBehavior('Jsonable');
		$this->Comments->addBehavior('Tools.Jsonable', array('fields' => array('details'), 'encodeParams' => array('depth' => 2)));

		$data = array(
			'comment' => 'blabla',
			'url' => 'www.dereuromark.de',
			'title' => 'param',
			'details' => array('x' => array('y' => 'z')),
		);
		$entity = $this->Comments->newEntity($data);
		$this->Comments->save($entity);

		$res = $this->Comments->find('all', array('conditions' => array('title' => 'param')))->first();
		debug($res); ob_flush();
		$obj = new \stdClass();
		$obj->y = 'z';
		$expected = array('x' => $obj);
		$this->assertEquals($expected, $res['details']);
	}

	/**
	 * JsonableBehaviorTest::testDecodeParams()
	 *
	 * @return void
	 */
	public function testDecodeParams() {
		$this->Comments->removeBehavior('Jsonable');
		$this->Comments->addBehavior('Tools.Jsonable', array('output' => 'array', 'fields' => array('details')));

		$data = array(
			'comment' => 'blabla',
			'url' => 'www.dereuromark.de',
			'title' => 'param',
			'details' => array('x' => array('y' => 'z')),
		);
		$entity = $this->Comments->newEntity($data);
		$this->Comments->save($entity);

		// Test decode with default params
		$res = $this->Comments->find('all', array('conditions' => array('title' => 'param')))->first();
		$obj = new \stdClass();
		$obj->y = 'z';
		$expected = array('x' => $obj);
		$this->assertEquals($expected, $res['details']);

		// Test decode with assoc = true
		$this->Comments->removeBehavior('Jsonable');
		$this->Comments->addBehavior('Tools.Jsonable', array('fields' => array('details'), 'decodeParams' => array('assoc' => true)));

		$res = $this->Comments->find('all', array('conditions' => array('title' => 'param')))->first();
		$expected = array('x' => array('y' => 'z'));
		$this->assertEquals($expected, $res['details']);

		// Test decode with assoc = true and depth = 2
		$this->Comments->removeBehavior('Jsonable');
		$this->Comments->addBehavior('Tools.Jsonable', array('fields' => array('details'), 'decodeParams' => array('assoc' => true, 'depth' => 2)));

		$res = $this->Comments->find('all', array('conditions' => array('title' => 'param')))->first();
		$expected = array();
		$this->assertEquals($expected, $res['details']);

		// Test decode with assoc = true and depth = 3
		$this->Comments->removeBehavior('Jsonable');
		$this->Comments->addBehavior('Tools.Jsonable', array('fields' => array('details'), 'decodeParams' => array('assoc' => true, 'depth' => 3)));

		$res = $this->Comments->find('all', array('conditions' => array('title' => 'param')))->first();
		$expected = array('x' => array('y' => 'z'));
		$this->assertEquals($expected, $res['details']);
	}
}
