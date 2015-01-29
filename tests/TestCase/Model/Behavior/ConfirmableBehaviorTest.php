<?php

namespace Tools\Test\TestCase\Model\Behavior;

use Cake\ORM\TableRegistry;
use Tools\TestSuite\TestCase;
//use Cake\Core\Configure;
use Tools\Model\Behavior\ConfirmableBehavior;

class ConfirmableBehaviorTest extends TestCase {

	public $ConfirmableBehavior;

	public $fixtures = array('plugin.Tools.SluggedArticles');

	public function setUp() {
		parent::setUp();
	}

	/**
	 * ConfirmableBehaviorTest::testBasicValidation()
	 *
	 * @return void
	 */
	public function testBasicValidation() {
		$this->Articles = TableRegistry::get('SluggedArticles');
		$this->Articles->addBehavior('Tools.Confirmable');

		$animal = $this->Articles->newEntity();

		$data = array(
			'name' => 'FooBar',
			'confirm' => '0'
		);
		$animal = $this->Articles->patchEntity($animal, $data);
		$this->assertNotEmpty($animal->errors());
		$this->assertSame(array('confirm' => array('notEmpty' => __d('tools', 'Please confirm the checkbox'))), $animal->errors());

		$data = array(
			'name' => 'FooBar',
			'confirm' => '1'
		);
		$animal = $this->Articles->patchEntity($animal, $data);
		$this->assertEmpty($animal->errors());
	}

	/**
	 * ConfirmableBehaviorTest::testBasicValidation()
	 *
	 * @return void
	 */
	public function testValidationThatHasBeenModifiedBefore() {
		$this->Articles = TableRegistry::get('SluggedArticles');
		/*
		$this->Articles->validator()->add('confirm', 'notEmpty', [
				'rule' => function ($value, $context) {
					return !empty($value);
				},
				'message' => __('Please select checkbox to continue.'),
				'requirePresence' => true,
				'allowEmpty' => false,
				'last' => true,
			]);
		$this->Articles->validator()->remove('confirm');
		*/

		$this->Articles->addBehavior('Tools.Confirmable');

		$animal = $this->Articles->newEntity();

		$data = array(
			'name' => 'FooBar',
			'confirm' => '0'
		);
		$animal = $this->Articles->patchEntity($animal, $data);
		$this->assertNotEmpty($animal->errors());

		$this->assertSame(array('confirm' => array('notEmpty' => __d('tools', 'Please confirm the checkbox'))), $animal->errors());

		$data = array(
			'name' => 'FooBar',
			'confirm' => '1'
		);
		$animal = $this->Articles->patchEntity($animal, $data);
		$this->assertEmpty($animal->errors());
	}

	/**
	 * ConfirmableBehaviorTest::testValidationFieldMissing()
	 *
	 * @return void
	 */
	public function testValidationFieldMissing() {
		$this->Articles = TableRegistry::get('SluggedArticles');
		$this->Articles->addBehavior('Tools.Confirmable');

		$animal = $this->Articles->newEntity();
		$data = array(
			'name' => 'FooBar'
		);
		$animal = $this->Articles->patchEntity($animal, $data);
		$this->assertSame(array('confirm' => array('This field is required')), $animal->errors());
	}

}
