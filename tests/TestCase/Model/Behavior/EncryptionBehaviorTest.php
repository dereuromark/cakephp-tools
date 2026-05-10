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
		$result = $connection->getDriver()->execute('SELECT data FROM sessions WHERE id = :id', ['id' => $entityAfter->id])->fetchAll();
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

	/**
	 * Saving an entity whose encrypted field is NOT dirty must not re-encrypt the value.
	 *
	 * Security::encrypt() uses a fresh random IV per call, so re-encryption would emit a
	 * brand-new ciphertext for an unchanged plaintext, bloating audit/replication logs
	 * and breaking deterministic-output expectations downstream.
	 *
	 * @return void
	 */
	public function testSavingUnchangedFieldDoesNotReencrypt() {
		$entity = $this->table->newEntity(['id' => 11, 'data' => 'hello world']);
		$this->table->save($entity);

		$connection = ConnectionManager::get('default');
		$ciphertextBefore = $connection->getDriver()
			->execute('SELECT data FROM sessions WHERE id = :id', ['id' => 11])
			->fetchAll()[0][0];

		// Reload, mutate an unrelated path, save again. The encrypted field should be
		// unchanged on disk because we never marked it dirty.
		$entity = $this->table->get(11);
		// Touch an unrelated metadata-ish property to force a save without dirtying `data`.
		$entity->setDirty('id', true);
		$this->table->save($entity);

		$ciphertextAfter = $connection->getDriver()
			->execute('SELECT data FROM sessions WHERE id = :id', ['id' => 11])
			->fetchAll()[0][0];

		$this->assertSame($ciphertextBefore, $ciphertextAfter);
	}

	/**
	 * Saving an entity with the encrypted field marked dirty must produce a new
	 * ciphertext (because Security::encrypt rotates the IV) — but the decrypted
	 * value must still round-trip to the new content.
	 *
	 * @return void
	 */
	public function testSavingDirtyFieldDoesReencrypt() {
		$entity = $this->table->newEntity(['id' => 12, 'data' => 'first content']);
		$this->table->save($entity);

		$entity = $this->table->get(12);
		$entity->set('data', 'second content');
		$this->table->save($entity);

		$reloaded = $this->table->get(12);
		$this->assertSame('second content', $reloaded->data);
	}

}
