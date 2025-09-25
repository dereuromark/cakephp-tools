<?php

namespace Tools\Test\TestCase\Model\Behavior;

use Shim\TestSuite\TestCase;

class ToggleBehaviorTest extends TestCase {

	/**
	 * @var array
	 */
	protected array $fixtures = [
		'plugin.Tools.ToggleAddresses',
	];

	/**
	 * @var \Tools\Model\Table\Table|\Tools\Model\Behavior\ToggleBehavior
	 */
	protected $Addresses;

	/**
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		$this->Addresses = $this->getTableLocator()->get('ToggleAddresses');
		$this->Addresses->addBehavior('Tools.Toggle', ['scopeFields' => 'category_id']);
	}

	/**
	 * @return void
	 */
	public function testSaveBasic() {
		$data = [
			'name' => 'Foo Bar',
			'category_id' => 2,
		];

		$entity = $this->Addresses->newEntity($data);
		$address = $this->Addresses->save($entity);
		$this->assertTrue((bool)$address);
		$this->assertTrue($address->primary);

		$data = [
			'name' => 'Foo Bar Ext',
			'category_id' => 2,
		];

		$entity2 = $this->Addresses->newEntity($data);
		$address = $this->Addresses->save($entity2);
		$this->assertTrue((bool)$address);

		$address = $this->Addresses->get($entity2->id);
		$this->assertFalse($address->primary);

		$address->primary = true;
		$address = $this->Addresses->save($address);
		$this->assertTrue((bool)$address);

		$address = $this->Addresses->get($address->id);
		$this->assertTrue($address->primary);

		$address = $this->Addresses->get($entity->id);
		$this->assertFalse($address->primary);
	}

	/**
	 * @return void
	 */
	public function testDelete() {
		$data = [
			'name' => 'Foo Bar',
			'category_id' => 2,
		];
		$entity = $this->Addresses->newEntity($data);
		$address = $this->Addresses->save($entity);
		$this->assertTrue((bool)$address);
		$this->assertTrue($address->primary);

		$data = [
			'name' => 'Foo Bar Ext',
			'category_id' => 2,
			'primary' => true,
		];
		$address2 = $this->Addresses->newEntity($data);
		$address2 = $this->Addresses->save($address2);
		$this->assertTrue((bool)$address2);

		$address = $this->Addresses->get($address->id);
		$this->assertFalse($address->primary);

		$address2 = $this->Addresses->get($address2->id);
		$this->assertTrue($address2->primary);

		$this->Addresses->delete($address2);
		$address = $this->Addresses->get($address->id);
		$this->assertTrue($address->primary);
	}

	/**
	 * @return void
	 */
	public function testToggleField() {
		$data = [
			'name' => 'Foo Bar',
			'category_id' => 2,
		];
		$entity = $this->Addresses->newEntity($data);
		$address = $this->Addresses->save($entity);
		$this->assertTrue((bool)$address);
		$this->assertTrue($address->primary);

		$data = [
			'name' => 'Foo Bar Ext',
			'category_id' => 2,
			'primary' => true,
		];
		$address2 = $this->Addresses->newEntity($data);
		$address2 = $this->Addresses->save($address2);
		$this->assertTrue((bool)$address2);

		$address = $this->Addresses->get($address->id);
		$this->assertFalse($address->primary);

		$address2 = $this->Addresses->get($address2->id);
		$this->assertTrue($address2->primary);

		$this->Addresses->toggleField($address);

		$address = $this->Addresses->get($address->id);
		$this->assertTrue($address->primary);

		$address2 = $this->Addresses->get($address2->id);
		$this->assertFalse($address2->primary);
	}

	/**
	 * Test that adding a new non-primary entity doesn't affect existing primary entities.
	 * This tests the fix where removeFromOthers was called unconditionally.
	 *
	 * @return void
	 */
	public function testAddNonPrimaryDoesNotAffectExistingPrimary() {
		// Create a primary entity
		$data = [
			'name' => 'Primary Address',
			'category_id' => 3,
			'primary' => true,
		];
		$primaryEntity = $this->Addresses->newEntity($data);
		$primaryAddress = $this->Addresses->save($primaryEntity);
		$this->assertTrue((bool)$primaryAddress);
		$this->assertTrue($primaryAddress->primary);

		// Add a non-primary entity (explicitly set to false)
		$data = [
			'name' => 'Secondary Address',
			'category_id' => 3,
			'primary' => false,
		];
		$secondaryEntity = $this->Addresses->newEntity($data);
		$secondaryAddress = $this->Addresses->save($secondaryEntity);
		$this->assertTrue((bool)$secondaryAddress);
		$this->assertFalse($secondaryAddress->primary);

		// Verify the primary entity is still primary
		$primaryAddress = $this->Addresses->get($primaryAddress->id);
		$this->assertTrue($primaryAddress->primary, 'Primary entity should remain primary when adding non-primary entity');

		// Add another non-primary entity (not set at all)
		// When no primary field is set and there's already a primary, it should remain false
		$data = [
			'name' => 'Third Address',
			'category_id' => 3,
		];
		$thirdEntity = $this->Addresses->newEntity($data);
		$thirdAddress = $this->Addresses->save($thirdEntity);
		$this->assertTrue((bool)$thirdAddress);
		$this->assertFalse((bool)$thirdAddress->primary);

		// Again verify the primary entity is still primary
		$primaryAddress = $this->Addresses->get($primaryAddress->id);
		$this->assertTrue($primaryAddress->primary, 'Primary entity should remain primary when adding another non-primary entity');
	}

	/**
	 * Test that setting an entity to primary removes it from others.
	 *
	 * @return void
	 */
	public function testSettingPrimaryRemovesFromOthers() {
		// Create primary entity
		$data = [
			'name' => 'First Primary',
			'category_id' => 4,
			'primary' => true,
		];
		$firstEntity = $this->Addresses->newEntity($data);
		$firstAddress = $this->Addresses->save($firstEntity);
		$this->assertTrue((bool)$firstAddress);
		$this->assertTrue($firstAddress->primary);

		// Create another entity and set it as primary
		$data = [
			'name' => 'Second Primary',
			'category_id' => 4,
			'primary' => true,
		];
		$secondEntity = $this->Addresses->newEntity($data);
		$secondAddress = $this->Addresses->save($secondEntity);
		$this->assertTrue((bool)$secondAddress);
		$this->assertTrue($secondAddress->primary);

		// Verify first is no longer primary
		$firstAddress = $this->Addresses->get($firstAddress->id);
		$this->assertFalse($firstAddress->primary, 'First entity should no longer be primary');

		// Update an existing non-primary to primary
		$data = [
			'name' => 'Third Address',
			'category_id' => 4,
			'primary' => false,
		];
		$thirdEntity = $this->Addresses->newEntity($data);
		$thirdAddress = $this->Addresses->save($thirdEntity);
		$this->assertTrue((bool)$thirdAddress);
		$this->assertFalse($thirdAddress->primary);

		// Now update it to be primary
		$thirdAddress->primary = true;
		$thirdAddress = $this->Addresses->save($thirdAddress);
		$this->assertTrue((bool)$thirdAddress);
		$this->assertTrue($thirdAddress->primary);

		// Verify second is no longer primary
		$secondAddress = $this->Addresses->get($secondAddress->id);
		$this->assertFalse($secondAddress->primary, 'Second entity should no longer be primary');
	}

	/**
	 * Test that updating non-primary fields doesn't affect primary status.
	 *
	 * @return void
	 */
	public function testUpdateNonPrimaryFieldDoesNotAffectPrimaryStatus() {
		// Create primary entity
		$data = [
			'name' => 'Primary Address',
			'category_id' => 5,
			'primary' => true,
		];
		$primaryEntity = $this->Addresses->newEntity($data);
		$primaryAddress = $this->Addresses->save($primaryEntity);
		$this->assertTrue((bool)$primaryAddress);
		$this->assertTrue($primaryAddress->primary);

		// Create non-primary entity
		$data = [
			'name' => 'Non-Primary Address',
			'category_id' => 5,
			'primary' => false,
		];
		$nonPrimaryEntity = $this->Addresses->newEntity($data);
		$nonPrimaryAddress = $this->Addresses->save($nonPrimaryEntity);
		$this->assertTrue((bool)$nonPrimaryAddress);
		$this->assertFalse($nonPrimaryAddress->primary);

		// Update only the name of non-primary entity (not touching primary field)
		$nonPrimaryAddress = $this->Addresses->get($nonPrimaryAddress->id);
		$nonPrimaryAddress->name = 'Updated Non-Primary Address';
		$nonPrimaryAddress = $this->Addresses->save($nonPrimaryAddress);
		$this->assertTrue((bool)$nonPrimaryAddress);
		$this->assertFalse($nonPrimaryAddress->primary);

		// Verify primary entity is still primary
		$primaryAddress = $this->Addresses->get($primaryAddress->id);
		$this->assertTrue($primaryAddress->primary, 'Primary entity should remain primary after updating non-primary fields of another entity');
	}

}
