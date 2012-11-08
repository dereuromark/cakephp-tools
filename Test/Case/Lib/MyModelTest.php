<?php

App::uses('MyModel', 'Tools.Lib');
App::uses('MyCakeTestCase', 'Tools.TestSuite');

class MyModelTest extends MyCakeTestCase {

	public $Model;

	public $fixtures = array('core.post', 'core.author');

	public function setUp() {
		parent::setUp();

		$this->Model = ClassRegistry::init('Post');
	}

	public function testObject() {
		$this->Model = ClassRegistry::init('MyModel');
		$this->assertTrue(is_object($this->Model));
		$this->assertIsA($this->Model, 'MyModel');
	}

	public function testGet() {
		$record = $this->Model->get(2);
		$this->assertEquals(2, $record['Post']['id']);

		$record = $this->Model->get(2, array('fields'=>'id', 'created'));
		$this->assertEquals(2, count($record['Post']));

		$record = $this->Model->get(2, array('fields'=>'id', 'title', 'body'), array('Author'));
		$this->assertEquals(3, $record['Author']['id']);

	}

}

class Post extends MyModel {

	public $belongsTo = 'Author';

}