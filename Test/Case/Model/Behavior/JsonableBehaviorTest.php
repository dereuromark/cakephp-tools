<?php

App::uses('JsonableBehavior', 'Tools.Model/Behavior');
App::uses('AppModel', 'Model');
App::uses('MyCakeTestCase', 'Tools.TestSuite');


class JsonableBehaviorTest extends MyCakeTestCase {

	public $fixtures = array(
		'plugin.tools.jsonable_comment'
	);

	public $Comment;

	public function setUp() {
		parent::setUp();

		$this->Comment = ClassRegistry::init('JsonableComment');
		$this->Comment->Behaviors->load('Tools.Jsonable', array());
	}

/** INPUT **/

	public function testBasic() {
		//echo $this->_header(__FUNCTION__);
		// accuracy >= 5
		$data = array(
			'comment' => 'blabla',
			'url' => 'www.dereuromark.de',
			'title' => 'some Name',
			'details' => array('x'=>'y'),
		);
		$res = $this->Comment->save($data);
		$this->assertTrue((bool)$res);

		$this->assertSame($res['JsonableComment']['details'], '{"x":"y"}');
	}

	public function testFieldsWithList() {
		//echo $this->_header(__FUNCTION__);
		$this->Comment->Behaviors->unload('Jsonable');
		$this->Comment->Behaviors->load('Tools.Jsonable', array('fields'=>array('details'), 'input'=>'list'));

		$data = array(
			'comment' => 'blabla',
			'url' => 'www.dereuromark.de',
			'title' => 'some Name',
			'details' => 'z|y|x',
		);
		$res = $this->Comment->save($data);
		$this->assertTrue((bool)$res);

		$this->assertSame($res['JsonableComment']['details'], '["z","y","x"]');

		# with sort and unique
		$data = array(
			'comment' => 'blabla',
			'url' => 'www.dereuromark.de',
			'title' => 'some Name',
			'details' => 'z|x|y|x',
		);
		$this->Comment->Behaviors->unload('Jsonable');
		$this->Comment->Behaviors->load('Tools.Jsonable', array('fields'=>array('details'), 'input'=>'list', 'sort'=>true));

		$res = $this->Comment->save($data);
		$this->assertTrue((bool)$res);

		$this->assertSame($res['JsonableComment']['details'], '["x","y","z"]');
	}

	public function testFieldsWithParam() {
		//echo $this->_header(__FUNCTION__);
		$this->Comment->Behaviors->unload('Jsonable');
		$this->Comment->Behaviors->load('Tools.Jsonable', array('fields'=>array('details'), 'input'=>'param'));

		$data = array(
			'comment' => 'blabla',
			'url' => 'www.dereuromark.de',
			'title' => 'some Name',
			'details' => 'z:vz|y:yz|x:xz',
		);
		$res = $this->Comment->save($data);
		$this->assertTrue((bool)$res);

		$this->assertSame($res['JsonableComment']['details'], '{"z":"vz","y":"yz","x":"xz"}');
	}



/** OUTPUT **/

	public function testFieldsOnFind() {
		//echo $this->_header(__FUNCTION__);

		// array
		$this->Comment->Behaviors->unload('Jsonable');
		$this->Comment->Behaviors->load('Tools.Jsonable', array('fields'=>array('details')));

		$data = array(
			'comment' => 'blabla',
			'url' => 'www.dereuromark.de',
			'title' => 'param',
			'details' => array('x'=>'y'),
		);
		$res = $this->Comment->save($data);
		$this->assertTrue((bool)$res);

		$res = $this->Comment->find('first', array('conditions' => array('title' => 'param')));
		$this->assertEquals($res['JsonableComment']['details'], array('x'=>'y'));

		// param
		$this->Comment->Behaviors->unload('Jsonable');

		$res = $this->Comment->find('first', array('conditions' => array('title' => 'param')));
		$this->assertEquals($res['JsonableComment']['details'], '{"x":"y"}');

		$this->Comment->Behaviors->load('Tools.Jsonable', array('output'=>'param', 'fields'=>array('details')));

		$res = $this->Comment->find('first', array('conditions' => array('title' => 'param')));
		$this->assertEquals($res['JsonableComment']['details'], 'x:y');

		// list
		$this->Comment->Behaviors->unload('Jsonable');
		$this->Comment->Behaviors->load('Tools.Jsonable', array('output'=>'list', 'fields'=>array('details')));

		$data = array(
			'comment' => 'blabla',
			'url' => 'www.dereuromark.de',
			'title' => 'list',
			'details' => array('z', 'y', 'x'),
		);
		$this->Comment->create();
		$res = $this->Comment->save($data);
		$this->assertTrue((bool)$res);

		$this->Comment->Behaviors->unload('Jsonable');

		$res = $this->Comment->find('first', array('conditions' => array('title' => 'list')));
		$this->assertEquals($res['JsonableComment']['details'], '["z","y","x"]');

		$this->Comment->Behaviors->load('Tools.Jsonable', array('output'=>'list', 'fields'=>array('details')));

		$res = $this->Comment->find('first', array('conditions' => array('title' => 'list')));
		$this->assertEquals($res['JsonableComment']['details'], 'z|y|x');

		// custom separator
		$this->Comment->Behaviors->unload('Jsonable');
		$this->Comment->Behaviors->load('Tools.Jsonable', array('output'=>'list', 'separator'=>', ', 'fields'=>array('details')));

		// find first
		$res = $this->Comment->find('first', array('conditions' => array('title' => 'list')));
		$this->assertEquals($res['JsonableComment']['details'], 'z, y, x');

		// find all
		$res = $this->Comment->find('all', array('order' => array('title' => 'ASC')));
		$this->assertEquals($res[0]['JsonableComment']['details'], 'z, y, x');
	}

}

