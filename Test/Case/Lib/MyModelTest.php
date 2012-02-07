<?php

App::uses('MyModel', 'Tools.Lib');
App::uses('MyCakeTestCase', 'Tools.Lib');

class MyModelTest extends MyCakeTestCase {

	public $MyModel;

	public function startTest() {
		$this->MyModel = new MyModel();
	}

	public function testObject() {
		$this->assertTrue(is_object($this->MyModel));
		$this->assertIsA($this->MyModel, 'MyModel');
	}

	//TODO
}