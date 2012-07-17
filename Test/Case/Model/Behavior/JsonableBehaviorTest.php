<?php

App::import('Behavior', 'Tools.Jsonable');
App::import('Model', 'App');
App::uses('MyCakeTestCase', 'Tools.Lib');

class JsonableTestModel extends AppModel {

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

class JsonableTest extends MyCakeTestCase {
	/*
	public $fixtures = array(
		'core.comment'
	);
	*/
	public $Comment;

	public function startTest() {
		//$this->Comment = ClassRegistry::init('Comment');
		$this->Comment = new JsonableTestModel();
		$this->Comment->Behaviors->attach('Jsonable', array());
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
		echo returns($res);
		$this->assertIdentical($res['JsonableTestModel']['details'], '{"x":"y"}');
	}

	public function testFieldsWithList() {
		echo $this->_header(__FUNCTION__);
		$this->Comment->Behaviors->detach('Jsonable');
		$this->Comment->Behaviors->attach('Jsonable', array('fields'=>array('details'), 'input'=>'list'));

		$data = array(
			'comment' => 'blabla',
			'url' => 'www.dereuromark.de',
			'name' => 'some Name',
			'details' => 'z|y|x',
		);
		$res = $this->Comment->save($data);
		$this->assertTrue($res);

		$res = $this->Comment->data;
		echo returns($res);
		$this->assertIdentical($res['JsonableTestModel']['details'], '["z","y","x"]');

		# with sort and unique
		$data = array(
			'comment' => 'blabla',
			'url' => 'www.dereuromark.de',
			'name' => 'some Name',
			'details' => 'z|x|y|x',
		);
		$this->Comment->Behaviors->detach('Jsonable');
		$this->Comment->Behaviors->attach('Jsonable', array('fields'=>array('details'), 'input'=>'list', 'sort'=>true));

		$res = $this->Comment->save($data);
		$this->assertTrue($res);

		$res = $this->Comment->data;
		echo returns($res);
		$this->assertIdentical($res['JsonableTestModel']['details'], '["x","y","z"]');
	}

	public function testFieldsWithParam() {
		echo $this->_header(__FUNCTION__);
		$this->Comment->Behaviors->detach('Jsonable');
		$this->Comment->Behaviors->attach('Jsonable', array('fields'=>array('details'), 'input'=>'param'));

		$data = array(
			'comment' => 'blabla',
			'url' => 'www.dereuromark.de',
			'name' => 'some Name',
			'details' => 'z:vz|y:yz|x:xz',
		);
		$res = $this->Comment->save($data);
		$this->assertTrue($res);

		$res = $this->Comment->data;
		echo returns($res);
		$this->assertIdentical($res['JsonableTestModel']['details'], '{"z":"vz","y":"yz","x":"xz"}');
	}



/** OUTPUT **/

	public function testFieldsOnFind() {
		echo $this->_header(__FUNCTION__);
		$this->Comment->Behaviors->detach('Jsonable');
		$this->Comment->Behaviors->attach('Jsonable', array('fields'=>array('details')));

		$res = $this->Comment->find('first', array());

		$this->assertEqual($res['JsonableTestModel']['details'], array('x'=>'y'));
		pr($res);


		$this->Comment->Behaviors->detach('Jsonable');
		$this->Comment->Behaviors->attach('Jsonable', array('output'=>'param', 'fields'=>array('details')));

		$res = $this->Comment->find('first', array());
		pr($res);
		$this->assertEqual($res['JsonableTestModel']['details'], 'x:y');




		$this->Comment->Behaviors->detach('Jsonable');
		$this->Comment->Behaviors->attach('Jsonable', array('output'=>'list', 'fields'=>array('details')));

		$data = array(
			'comment' => 'blabla',
			'url' => 'www.dereuromark.de',
			'name' => 'some Name',
			'details' => '["z","y","x"]',
		);
		$res = $this->Comment->find('first', array(), $data);
		pr($res);
		$this->assertEqual($res['JsonableTestModel']['details'], 'z|y|x');

		echo BR.BR;


		$this->Comment->Behaviors->detach('Jsonable');
		$this->Comment->Behaviors->attach('Jsonable', array('output'=>'list', 'separator'=>', ', 'fields'=>array('details')));

		$data = array(
			'comment' => 'blabla',
			'url' => 'www.dereuromark.de',
			'name' => 'some Name',
			'details' => '["z","y","x"]',
		);
		$res = $this->Comment->find('first', array(), $data);
		pr($res);
		$this->assertEqual($res['JsonableTestModel']['details'], 'z, y, x');


		echo BR.BR;

		$res = $this->Comment->find('all', array(), $data);
		pr($res);
		$this->assertEqual($res[0]['JsonableTestModel']['details'], 'z, y, x');
	}
}
