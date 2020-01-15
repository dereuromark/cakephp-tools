<?php

namespace Tools\Test\TestCase\Model\Behavior;

use Cake\ORM\TableRegistry;
use Shim\TestSuite\TestCase;

class TypeMapBehaviorTest extends TestCase {

	/**
	 * @var array
	 */
	protected $fixtures = [
		'plugin.Tools.Data',
	];

	/**
	 * @var \Tools\Model\Behavior\TypeMapBehavior
	 */
	protected $TypeMapBehavior;

	/**
	 * @var \Tools\Model\Table\Table
	 */
	protected $Table;

	/**
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();
	}

	/**
	 * Tests that we can disable array conversion for edit forms if we need to modify the JSON directly.
	 *
	 * @return void
	 */
	public function testFields() {
		$this->Table = TableRegistry::getTableLocator()->get('Data');
		$this->Table->addBehavior('Tools.Jsonable', ['fields' => ['data_array']]);

		$entity = $this->Table->newEmptyEntity();

		$data = [
			'name' => 'FooBar',
			'data_json' => ['x' => 'y'],
			'data_array' => ['x' => 'y'],
		];
		$entity = $this->Table->patchEntity($entity, $data);
		$this->assertEmpty($entity->getErrors());

		$this->Table->saveOrFail($entity);
		$this->assertSame($data['data_json'], $entity->data_json);
		$this->assertSame('{"x":"y"}', $entity->data_array);

		$savedEntity = $this->Table->get($entity->id);

		$this->assertSame($data['data_json'], $savedEntity->data_json);
		$this->assertSame($data['data_array'], $savedEntity->data_array);

		// Now let's disable the array conversion per type
		$this->Table->removeBehavior('Jsonable');
		$this->Table->addBehavior('Tools.TypeMap', ['fields' => ['data_json' => 'text']]);
		$entity = $this->Table->get($entity->id);

		$this->assertSame('{"x":"y"}', $entity->data_json);
		$this->assertSame('{"x":"y"}', $entity->data_array);

		$data = [
			'data_json' => '{"x":"z"}',
			'data_array' => '{"x":"z"}',
		];
		$entity = $this->Table->patchEntity($entity, $data);
		$this->Table->saveOrFail($entity);

		$savedEntity = $this->Table->get($entity->id);
		$this->assertSame($data['data_json'], $savedEntity->data_json);
		$this->assertSame($data['data_array'], $savedEntity->data_array);
	}

}
