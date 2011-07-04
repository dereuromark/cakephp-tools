<?php

App::import('Behavior', 'Tools.Jsonable');
App::import('Model', 'App');
App::import('Vendor', 'MyCakeTestCase');

class TestModel extends AppModel {
	
	var $name = 'TestModel';
	var $alias = 'TestModel';
	var $useTable = false;
	var $displayField = 'title';
	
	function find($type = null, $options = array(), $customData = null) {
		$data = array(
			'comment' => 'blabla',
			'url' => 'www.dereuromark.de',
			'name' => 'some Name',
			'details' => '{"x":"y"}',
		);
		if ($customData !== null) {
			$data = $customData;
		}
		$results = array($this->alias=>$data);
		$results = $this->__filterResults($results);
		return $results;
	}
} 

class JsonableTestCase extends MyCakeTestCase {
	/*
	var $fixtures = array(
		'core.comment'
	);
	*/
	var $Comment;

	function startTest() {
		//$this->Comment =& ClassRegistry::init('Comment');
		$this->Comment = new TestModel();
		$this->Comment->Behaviors->attach('Jsonable', array());
	}

/** INPUT **/

	function testBasic() {
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
		$this->assertIdentical($res['TestModel']['details'], '{"x":"y"}');
	}

	function testFieldsWithList() {
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
		$this->assertIdentical($res['TestModel']['details'], '["z","y","x"]');
		
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
		$this->assertIdentical($res['TestModel']['details'], '["x","y","z"]');
	}
	
	function testFieldsWithParam() {
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
		$this->assertIdentical($res['TestModel']['details'], '{"z":"vz","y":"yz","x":"xz"}');
	}
	


/** OUTPUT **/

	function _testFieldsOnFind() {
		echo $this->_header(__FUNCTION__);
		$this->Comment->Behaviors->detach('Jsonable');
		$this->Comment->Behaviors->attach('Jsonable', array('fields'=>array('details')));
		
		$res = $this->Comment->find('first', array());
		
		$this->assertEqual($res['TestModel']['details'], array('x'=>'y'));
		pr($res);
		
		
		$this->Comment->Behaviors->detach('Jsonable');
		$this->Comment->Behaviors->attach('Jsonable', array('output'=>'param', 'fields'=>array('details')));
		
		$res = $this->Comment->find('first', array());
		
		$this->assertEqual($res['TestModel']['details'], array('{"x":"y"}'));
		pr($res);
		
		
		
		$this->Comment->Behaviors->detach('Jsonable');
		$this->Comment->Behaviors->attach('Jsonable', array('output'=>'list', 'fields'=>array('details')));
		
		$data = array(
			'comment' => 'blabla',
			'url' => 'www.dereuromark.de',
			'name' => 'some Name',
			'details' => '["z","y","x"]',
		);
		$res = $this->Comment->find('first', array(), $data);
		
		$this->assertEqual($res['TestModel']['details'], array('{"x":"y"}'));
		pr($res);
	}
}

