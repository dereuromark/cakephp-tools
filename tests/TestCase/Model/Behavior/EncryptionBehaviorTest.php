<?php

namespace Tools\Test\TestCase\Model\Behavior;

use Cake\Datasource\ConnectionManager;
use Shim\TestSuite\TestCase;

class EncryptionBehaviorTest extends TestCase {

	/**
	 * @var array
	 */
	protected array $fixtures = [
		'plugin.Tools.Sessions',
	];

	/**
	 * @var \Tools\Model\Table\Table|\Tools\Model\Behavior\EncryptionBehavior
	 */
	protected $table;

	/**
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		$this->table = $this->getTableLocator()->get('Sessions');
		$this->table->addBehavior('Tools.Encryption', [
			'fields' => ['data'],
			'key' => 'some-very-long-key-which-needs-to-be-at-least-32-chars-long',
		]);
	}

	/**
	 * @return void
	 */
	public function testSaveBasic() {
		$data = [
			'id' => 10,
			'data' => 'test save',
		];

		$entity = $this->table->newEntity($data);
		$entityAfter = $this->table->save($entity);
		$this->assertTrue((bool)$entityAfter);

		$connection = ConnectionManager::get('default');
		$lastInsertedId = $connection->getDriver()->lastInsertId();
		$result = $connection->getDriver()->execute('SELECT data FROM sessions WHERE id = :id', ['id' => $lastInsertedId])->fetchAll();
		$this->assertNotEquals($data['data'], $result[0][0]);
	}

	/**
	 * @return void
	 */
	public function testFindBasic() {
		$data = [
			'id' => 10,
			'data' => 'test save',
		];
		$entity = $this->table->newEntity($data);
		$this->table->save($entity);

		$entity = $this->table->get(10);
		$this->assertEquals('test save', $entity->data);
	}

}
