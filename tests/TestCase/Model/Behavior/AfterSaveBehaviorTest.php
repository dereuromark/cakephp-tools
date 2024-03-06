<?php

namespace Tools\Test\TestCase\Model\Behavior;

use Shim\TestSuite\TestCase;
use TestApp\Model\Table\PostsTable;

class AfterSaveBehaviorTest extends TestCase {

	/**
	 * @var array
	 */
	protected array $fixtures = [
		'plugin.Tools.Articles',
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

		$this->table = $this->getTableLocator()->get('Articles');
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
		$this->assertSame(['body' => 'test save'], $entityBefore->extractOriginal(['id', 'body']));
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
		$this->assertSame(['body' => 'modified'], $entityAfter->extractOriginal(['body']));

		$entityBefore = $this->table->getEntityBeforeSave();

		// The stored one from before the save contains the info we want
		$this->assertTrue($entityBefore->isDirty('body'));
		$this->assertSame(['body' => 'test save'], $entityBefore->extractOriginal(['body']));
	}

	/**
	 * @return void
	 */
	public function testAfterSaveCallbackWihoutBehavior() {
		$table = $this->getTableLocator()->get('Posts');
		$this->assertInstanceOf(PostsTable::class, $table);

		$entity = $table->newEmptyEntity();
		$entity = $table->patchEntity($entity, ['author_id' => 1, 'title' => 'Some title']);

		$this->assertNotEmpty($entity->getDirty());

		$table->saveOrFail($entity);

		$dirty = $entity->getDirty();
		$this->assertSame([], $dirty);

		$dirtyBefore = $table->dirtyFieldsBefore;
		$dirtyAfter = $table->dirtyFieldsAfter;

		$this->assertSame(['author_id', 'title'], $dirtyBefore);
		$this->assertSame(['author_id', 'title', 'id'], $dirtyAfter);

		// Now we edit existing entity with only one field
		$entity->title = 'New title';
		$table->saveOrFail($entity);

		$dirtyAfter = $table->dirtyFieldsAfter;
		$this->assertSame(['title'], $dirtyAfter);
	}

}
