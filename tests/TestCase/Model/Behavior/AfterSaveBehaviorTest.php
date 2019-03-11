<?php

namespace Tools\Test\TestCase\Model\Behavior;

use Cake\ORM\TableRegistry;
use Tools\TestSuite\TestCase;

class AfterSaveBehaviorTest extends TestCase {

	/**
	 * @var array
	 */
	public $fixtures = [
		'core.Articles'
	];

	/**
	 * @var \Tools\Model\Table\Table|\Tools\Model\Behavior\AfterSaveBehavior
	 */
	public $table;

	/**
	 * @return void
	 */
	public function setUp() {
		parent::setUp();

		$this->table = TableRegistry::getTableLocator()->get('Articles');
		$this->table->addBehavior('Tools.AfterSave');
	}

	/**
	 * @return void
	 */
	public function testSaveBasic() {
		$data = [
			'body' => 'test save',
		];

		$entity = $this->table->newEntity($data);
		$entityAfter = $this->table->save($entity);
		$this->assertTrue((bool)$entityAfter);

		// The saved entity is resetted
		$this->assertFalse($entityAfter->isDirty('body'));

		$entityBefore = $this->table->getEntityBeforeSave();

		// The stored one from before the save contains the info we want
		$this->assertTrue($entityBefore->isDirty('body'));
	}

}
