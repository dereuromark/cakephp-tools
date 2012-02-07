<?php

App::uses('SluggedBehavior', 'Tools.Model/Behavior');
App::uses('MyCakeTestCase', 'Tools.Lib');

class SluggedBehaviorTest extends MyCakeTestCase {

	public $SluggedBehavior;

	public function startTest() {
		$this->SluggedBehavior = new SluggedBehavior();
	}

	public function testObject() {
		$this->assertTrue(is_object($this->SluggedBehavior));
		$this->assertIsA($this->SluggedBehavior, 'SluggedBehavior');
	}

	//TODO
}