<?php

App::uses('ConfirmableBehavior', 'Tools.Model/Behavior');
App::uses('ModelBehavior', 'Model');
App::uses('MyCakeTestCase', 'Tools.Lib');

class ConfirmableBehaviorTest extends MyCakeTestCase {

	public $ConfirmableBehavior;

	public function startTest() {
		$this->ConfirmableBehavior = new ConfirmableBehavior();
	}

	public function testObject() {
		$this->assertTrue(is_object($this->ConfirmableBehavior));
		$this->assertIsA($this->ConfirmableBehavior, 'ConfirmableBehavior');
	}

	//TODO
}