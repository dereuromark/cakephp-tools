<?php

App::uses('JsonableBehavior', 'Tools.Model/Behavior');
App::uses('AppModel', 'Model');
App::uses('MyCakeTestCase', 'Tools.TestSuite');


class JsonableBehaviorTest extends MyCakeTestCase {

	public $fixtures = array(
		'core.comment'
	);

	public $Comment;

	public function setUp() {
		//$this->Comment = ClassRegistry::init('Comment');
		$this->Comment = new JsonableBehaviorTestModel();
		$this->Comment->Behaviors->load('Tools.Jsonable', array());
	}

/** INPUT **/

	public function testBasic() {
		echo $this->_header(__FUNCTION__);
		// accuracy >= 5
		$data = array(
			'comment' => 'blabla',
			'url' => 'www.dereuromark.de',
			'name' => 'some Name',
			'details' => array('x'=>'y'),
		);
		$res = $this->Comment->save($data);
		$this->assertTrue($res);

		$res = $this->Comment->data;
		debug($res); ob_flush();
		$this->assertSame($res['JsonableBehaviorTestModel']['details'], '{"x":"y"}');
	}

	public function testFieldsWithList() {
		echo $this->_header(__FUNCTION__);
		$this->Comment->Behaviors->unload('Jsonable');
		$this->Comment->Behaviors->load('Tools.Jsonable', array('fields'=>array('details'), 'input'=>'list'));

		$data = array(
			'comment' => 'blabla',
			'url' => 'www.dereuromark.de',
			'name' => 'some Name',
			'details' => 'z|y|x',
		);
		$res = $this->Comment->save($data);
		$this->assertTrue($res);

		$res = $this->Comment->data;
		debug($res);
		$this->assertSame($res['JsonableBehaviorTestModel']['details'], '["z","y","x"]');

		# with sort and unique
		$data = array(
			'comment' => 'blabla',
			'url' => 'www.dereuromark.de',
			'name' => 'some Name',
			'details' => 'z|x|y|x',
		);
		$this->Comment->Behaviors->unload('Jsonable');
		$this->Comment->Behaviors->load('Tools.Jsonable', array('fields'=>array('details'), 'input'=>'list', 'sort'=>true));

		$res = $this->Comment->save($data);
		$this->assertTrue($res);

		$res = $this->Comment->data;
		debug($res);
		$this->assertSame($res['JsonableBehaviorTestModel']['details'], '["x","y","z"]');
	}

	public function testFieldsWithParam() {
		echo $this->_header(__FUNCTION__);
		$this->Comment->Behaviors->unload('Jsonable');
		$this->Comment->Behaviors->load('Tools.Jsonable', array('fields'=>array('details'), 'input'=>'param'));

		$data = array(
			'comment' => 'blabla',
			'url' => 'www.dereuromark.de',
			'name' => 'some Name',
			'details' => 'z:vz|y:yz|x:xz',
		);
		$res = $this->Comment->save($data);
		$this->assertTrue($res);

		$res = $this->Comment->data;
		debug($res);
		$this->assertSame($res['JsonableBehaviorTestModel']['details'], '{"z":"vz","y":"yz","x":"xz"}');
	}



/** OUTPUT **/

	public function testFieldsOnFind() {
		echo $this->_header(__FUNCTION__);
		$this->Comment->Behaviors->unload('Jsonable');
		$this->Comment->Behaviors->load('Tools.Jsonable', array('fields'=>array('details')));

		$res = $this->Comment->find('first', array());

		$this->assertEquals($res['JsonableBehaviorTestModel']['details'], array('x'=>'y'));
		pr($res);


		$this->Comment->Behaviors->unload('Jsonable');
		$this->Comment->Behaviors->load('Tools.Jsonable', array('output'=>'param', 'fields'=>array('details')));

		$res = $this->Comment->find('first', array());
		pr($res);
		$this->assertEquals($res['JsonableBehaviorTestModel']['details'], 'x:y');




		$this->Comment->Behaviors->unload('Jsonable');
		$this->Comment->Behaviors->load('Tools.Jsonable', array('output'=>'list', 'fields'=>array('details')));

		$data = array(
			'comment' => 'blabla',
			'url' => 'www.dereuromark.de',
			'name' => 'some Name',
			'details' => '["z","y","x"]',
		);
		$res = $this->Comment->find('first', array(), $data);
		pr($res);
		$this->assertEquals($res['JsonableBehaviorTestModel']['details'], 'z|y|x');

		echo BR.BR;


		$this->Comment->Behaviors->unload('Jsonable');
		$this->Comment->Behaviors->load('Tools.Jsonable', array('output'=>'list', 'separator'=>', ', 'fields'=>array('details')));

		$data = array(
			'comment' => 'blabla',
			'url' => 'www.dereuromark.de',
			'name' => 'some Name',
			'details' => '["z","y","x"]',
		);
		$res = $this->Comment->find('first', array(), $data);
		pr($res);
		$this->assertEquals($res['JsonableBehaviorTestModel']['details'], 'z, y, x');


		echo BR.BR;

		$res = $this->Comment->find('all', array(), $data);
		pr($res);
		$this->assertEquals($res[0]['JsonableBehaviorTestModel']['details'], 'z, y, x');
	}
}

/*** other files ***/

class JsonableBehaviorTestModel extends AppModel {

	public $useTable = false;
	public $displayField = 'title';

	public function find($type = null, $options = array(), $customData = null) {
		$data = array(
			'comment' => 'blabla',
			'url' => 'www.dereuromark.de',
			'name' => 'some Name',
			'details' => '{"x":"y"}',
		);
		if ($customData !== null) {
			$data = $customData;
		}
		if ($type == 'count') {
			$results = array(0=>array(0=>array('count'=>2)));
		} else {
			$results = array(0=>array($this->alias=>$data));
		}

		$results = $this->_filterResults($results);
		if ($type == 'first') {
			$results = $this->_findFirst('after', null, $results);
		}
		return $results;
	}
}
