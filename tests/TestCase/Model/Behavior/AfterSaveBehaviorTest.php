<?php

namespace Tools\Test\TestCase\Model\Behavior;

use Cake\ORM\TableRegistry;
use Shim\TestSuite\TestCase;

class AfterSaveBehaviorTest extends TestCase {

	/**
	 * @var array
	 */
	protected $fixtures = [
		'core.Articles',
	];

	/**
	 * @var \Tools\Model\Table\Table|\Tools\Model\Behavior\AfterSaveBehavior
	 */
	protected $table;

	/**
	 * @return void
	 */
	public function setUp(): void {
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
		$this->assertFalse($entityAfter->isNew());
		$this->assertSame(['id' => 4, 'body' => 'test save'], $entityAfter->extractOriginal(['id', 'body']));

		$entityBefore = $this->table->getEntityBeforeSave();

		// The stored one from before the save contains the info we want
		$this->assertTrue($entityBefore->isDirty('body'));
		$this->assertTrue($entityBefore->isNew());
		$this->assertSame(['id' => null, 'body' => 'test save'], $entityBefore->extractOriginal(['id', 'body']));
	}

	/**
	 * @return void
	 */
	public function testSaveExisting() {
		$data = [
			'body' => 'test save',
		];

		$entity = $this->table->newEntity($data);
		$this->table->saveOrFail($entity);

		$entity = $this->table->get($entity->id);
		$entity = $this->table->patchEntity($entity, ['body' => 'modified']);
		$this->assertEmpty($entity->getErrors());

		$entityAfter = $this->table->save($entity);
		$this->assertTrue((bool)$entityAfter);

		// The saved entity is resetted
		$this->assertFalse($entityAfter->isDirty('body'));
		$this->assertSame(['id' => 4, 'body' => 'modified'], $entityAfter->extractOriginal(['id', 'body']));

		$entityBefore = $this->table->getEntityBeforeSave();

		// The stored one from before the save contains the info we want
		$this->assertTrue($entityBefore->isDirty('body'));
		$this->assertSame(['id' => 4, 'body' => 'test save'], $entityBefore->extractOriginal(['id', 'body']));
	}

}
