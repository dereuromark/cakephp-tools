<?php

App::uses('JsonableBehavior', 'Tools.Model/Behavior');
App::uses('AppModel', 'Model');
App::uses('MyCakeTestCase', 'Tools.TestSuite');

class JsonableBehaviorTest extends MyCakeTestCase {

	public $fixtures = [
		'plugin.tools.jsonable_comment'
	];

	public $Comment;

	public function setUp() {
		parent::setUp();

		$this->Comment = ClassRegistry::init('JsonableComment');
		$this->Comment->Behaviors->load('Tools.Jsonable', []);
	}

/** INPUT **/

	public function testBasic() {
		//echo $this->_header(__FUNCTION__);
		// accuracy >= 5
		$data = [
			'comment' => 'blabla',
			'url' => 'www.dereuromark.de',
			'title' => 'some Name',
			'details' => ['x' => 'y'],
		];
		$res = $this->Comment->save($data);
		$this->assertTrue((bool)$res);

		$this->assertSame('{"x":"y"}', $res['JsonableComment']['details']);
	}

	public function testFieldsWithList() {
		//echo $this->_header(__FUNCTION__);
		$this->Comment->Behaviors->unload('Jsonable');
		$this->Comment->Behaviors->load('Tools.Jsonable', ['fields' => ['details'], 'input' => 'list']);

		$data = [
			'comment' => 'blabla',
			'url' => 'www.dereuromark.de',
			'title' => 'some Name',
			'details' => 'z|y|x',
		];
		$res = $this->Comment->save($data);
		$this->assertTrue((bool)$res);

		$this->assertSame('["z","y","x"]', $res['JsonableComment']['details']);

		// with sort and unique
		$data = [
			'comment' => 'blabla',
			'url' => 'www.dereuromark.de',
			'title' => 'some Name',
			'details' => 'z|x|y|x',
		];
		$this->Comment->Behaviors->unload('Jsonable');
		$this->Comment->Behaviors->load('Tools.Jsonable', ['fields' => ['details'], 'input' => 'list', 'sort' => true]);

		$res = $this->Comment->save($data);
		$this->assertTrue((bool)$res);

		$this->assertSame('["x","y","z"]', $res['JsonableComment']['details']);
	}

	public function testFieldsWithParam() {
		//echo $this->_header(__FUNCTION__);
		$this->Comment->Behaviors->unload('Jsonable');
		$this->Comment->Behaviors->load('Tools.Jsonable', ['fields' => ['details'], 'input' => 'param']);

		$data = [
			'comment' => 'blabla',
			'url' => 'www.dereuromark.de',
			'title' => 'some Name',
			'details' => 'z:vz|y:yz|x:xz',
		];
		$res = $this->Comment->save($data);
		$this->assertTrue((bool)$res);

		$this->assertSame('{"z":"vz","y":"yz","x":"xz"}', $res['JsonableComment']['details']);
	}

/** OUTPUT **/

	public function testFieldsOnFind() {
		//echo $this->_header(__FUNCTION__);

		// array
		$this->Comment->Behaviors->unload('Jsonable');
		$this->Comment->Behaviors->load('Tools.Jsonable', ['fields' => ['details']]);

		$data = [
			'comment' => 'blabla',
			'url' => 'www.dereuromark.de',
			'title' => 'param',
			'details' => ['x' => 'y'],
		];
		$res = $this->Comment->save($data);
		$this->assertTrue((bool)$res);

		$res = $this->Comment->find('first', ['conditions' => ['title' => 'param']]);
		$this->assertEquals(['x' => 'y'], $res['JsonableComment']['details']);

		// param
		$this->Comment->Behaviors->unload('Jsonable');

		$res = $this->Comment->find('first', ['conditions' => ['title' => 'param']]);
		$this->assertEquals('{"x":"y"}', $res['JsonableComment']['details']);

		$this->Comment->Behaviors->load('Tools.Jsonable', ['output' => 'param', 'fields' => ['details']]);

		$res = $this->Comment->find('first', ['conditions' => ['title' => 'param']]);
		$this->assertEquals('x:y', $res['JsonableComment']['details']);

		// list
		$this->Comment->Behaviors->unload('Jsonable');
		$this->Comment->Behaviors->load('Tools.Jsonable', ['output' => 'list', 'fields' => ['details']]);

		$data = [
			'comment' => 'blabla',
			'url' => 'www.dereuromark.de',
			'title' => 'list',
			'details' => ['z', 'y', 'x'],
		];
		$this->Comment->create();
		$res = $this->Comment->save($data);
		$this->assertTrue((bool)$res);

		$this->Comment->Behaviors->unload('Jsonable');

		$res = $this->Comment->find('first', ['conditions' => ['title' => 'list']]);
		$this->assertEquals('["z","y","x"]', $res['JsonableComment']['details']);

		$this->Comment->Behaviors->load('Tools.Jsonable', ['output' => 'list', 'fields' => ['details']]);

		$res = $this->Comment->find('first', ['conditions' => ['title' => 'list']]);
		$this->assertEquals('z|y|x', $res['JsonableComment']['details']);

		// custom separator
		$this->Comment->Behaviors->unload('Jsonable');
		$this->Comment->Behaviors->load('Tools.Jsonable', ['output' => 'list', 'separator' => ', ', 'fields' => ['details']]);

		// find first
		$res = $this->Comment->find('first', ['conditions' => ['title' => 'list']]);
		$this->assertEquals('z, y, x', $res['JsonableComment']['details']);

		// find all
		$res = $this->Comment->find('all', ['order' => ['title' => 'ASC']]);
		$this->assertEquals('z, y, x', $res[0]['JsonableComment']['details']);
	}

	public function testEncodeParams() {
		// $depth param added in 5.5.0
		$this->skipIf(!version_compare(PHP_VERSION, '5.5.0', '>='));

		// Test encode depth = 1
		$this->Comment->Behaviors->unload('Jsonable');
		$this->Comment->Behaviors->load('Tools.Jsonable', ['fields' => ['details'], 'encodeParams' => ['depth' => 1]]);

		$data = [
			'comment' => 'blabla',
			'url' => 'www.dereuromark.de',
			'title' => 'param',
			'details' => ['x' => ['y' => 'z']],
		];
		$this->Comment->save($data);

		$res = $this->Comment->find('first', ['conditions' => ['title' => 'param']]);
		$expected = [];
		$this->assertEquals($expected, $res['JsonableComment']['details']);

		// Test encode depth = 2
		$this->Comment->Behaviors->unload('Jsonable');
		$this->Comment->Behaviors->load('Tools.Jsonable', ['fields' => ['details'], 'encodeParams' => ['depth' => 2]]);

		$data = [
			'comment' => 'blabla',
			'url' => 'www.dereuromark.de',
			'title' => 'param',
			'details' => ['x' => ['y' => 'z']],
		];
		$this->Comment->save($data);

		$res = $this->Comment->find('first', ['conditions' => ['title' => 'param']]);
		$obj = new stdClass();
		$obj->y = 'z';
		$expected = ['x' => $obj];
		$this->assertEquals($expected, $res['JsonableComment']['details']);
	}

	public function testDecodeParams() {
		$this->Comment->Behaviors->load('Tools.Jsonable', ['fields' => ['details']]);

		$data = [
			'comment' => 'blabla',
			'url' => 'www.dereuromark.de',
			'title' => 'param',
			'details' => ['x' => ['y' => 'z']],
		];
		$this->Comment->save($data);

		// Test decode with default params
		$res = $this->Comment->find('first', ['conditions' => ['title' => 'param']]);
		$obj = new stdClass();
		$obj->y = 'z';
		$expected = ['x' => $obj];
		$this->assertEquals($expected, $res['JsonableComment']['details']);

		// Test decode with assoc = true
		$this->Comment->Behaviors->unload('Jsonable');
		$this->Comment->Behaviors->load('Tools.Jsonable', ['fields' => ['details'], 'decodeParams' => ['assoc' => true]]);

		$res = $this->Comment->find('first', ['conditions' => ['title' => 'param']]);
		$expected = ['x' => ['y' => 'z']];
		$this->assertEquals($expected, $res['JsonableComment']['details']);

		// Test decode with assoc = true and depth = 2
		$this->Comment->Behaviors->unload('Jsonable');
		$this->Comment->Behaviors->load('Tools.Jsonable', ['fields' => ['details'], 'decodeParams' => ['assoc' => true, 'depth' => 2]]);

		$res = $this->Comment->find('first', ['conditions' => ['title' => 'param']]);
		$expected = [];
		$this->assertEquals($expected, $res['JsonableComment']['details']);

		// Test decode with assoc = true and depth = 3
		$this->Comment->Behaviors->unload('Jsonable');
		$this->Comment->Behaviors->load('Tools.Jsonable', ['fields' => ['details'], 'decodeParams' => ['assoc' => true, 'depth' => 3]]);

		$res = $this->Comment->find('first', ['conditions' => ['title' => 'param']]);
		$expected = ['x' => ['y' => 'z']];
		$this->assertEquals($expected, $res['JsonableComment']['details']);
	}
}
