<?php

App::uses('ConfirmableBehavior', 'Tools.Model/Behavior');
App::uses('ModelBehavior', 'Model');
App::uses('MyCakeTestCase', 'Tools.TestSuite');

class ConfirmableBehaviorTest extends MyCakeTestCase {

	public $ConfirmableBehavior;

	public function setUp() {
		parent::setUp();

		$this->ConfirmableBehavior = new ConfirmableBehavior();
	}

	public function testObject() {
		$this->assertTrue(is_object($this->ConfirmableBehavior));
		$this->assertInstanceOf('ConfirmableBehavior', $this->ConfirmableBehavior);
	}

	//TODO
}
