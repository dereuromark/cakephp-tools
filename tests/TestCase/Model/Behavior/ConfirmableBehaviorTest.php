<?php

namespace Tools\Test\TestCase\Model\Behavior;

use Cake\ORM\TableRegistry;
use Shim\TestSuite\TestCase;

class ConfirmableBehaviorTest extends TestCase {

	/**
	 * @var array
	 */
	protected $fixtures = [
		'plugin.Tools.SluggedArticles',
	];

	/**
	 * @var \Tools\Model\Behavior\ConfirmableBehavior
	 */
	protected $ConfirmableBehavior;

	/**
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();
	}

	/**
	 * ConfirmableBehaviorTest::testBasicValidation()
	 *
	 * @return void
	 */
	public function testBasicValidation() {
		$this->Articles = TableRegistry::getTableLocator()->get('SluggedArticles');
		$this->Articles->addBehavior('Tools.Confirmable');

		$animal = $this->Articles->newEmptyEntity();

		$data = [
			'name' => 'FooBar',
			'confirm' => '0',
		];
		$animal = $this->Articles->patchEntity($animal, $data);
		$this->assertNotEmpty($animal->getErrors());
		$this->assertSame(['confirm' => ['notBlank' => __d('tools', 'Please confirm the checkbox')]], $animal->getErrors());

		$data = [
			'name' => 'FooBar',
			'confirm' => '1',
		];
		$animal = $this->Articles->patchEntity($animal, $data);
		$this->assertEmpty($animal->getErrors());
	}

	/**
	 * @return void
	 */
	public function testValidationThatHasBeenModifiedBefore() {
		$this->Articles = TableRegistry::getTableLocator()->get('SluggedArticles');
		/*
		$this->Articles->validator()->add('confirm', 'notBlank', [
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

		$animal = $this->Articles->newEmptyEntity();

		$data = [
			'name' => 'FooBar',
			'confirm' => '0',
		];
		$animal = $this->Articles->patchEntity($animal, $data);
		$this->assertNotEmpty($animal->getErrors());

		$this->assertSame(['confirm' => ['notBlank' => __d('tools', 'Please confirm the checkbox')]], $animal->getErrors());

		$data = [
			'name' => 'FooBar',
			'confirm' => '1',
		];
		$animal = $this->Articles->patchEntity($animal, $data);
		$this->assertEmpty($animal->getErrors());
	}

	/**
	 * @return void
	 */
	public function testValidationFieldMissing() {
		$this->Articles = TableRegistry::getTableLocator()->get('SluggedArticles');
		$this->Articles->addBehavior('Tools.Confirmable');

		$animal = $this->Articles->newEmptyEntity();
		$data = [
			'name' => 'FooBar',
		];
		$animal = $this->Articles->patchEntity($animal, $data);
		$this->assertSame(['confirm' => ['_required' => 'This field is required']], $animal->getErrors());
	}

}
