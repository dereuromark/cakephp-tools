<?php

namespace TestApp\Model\Table;

use ArrayObject;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Tools\Model\Table\Table;

class PostsTable extends Table {

	/**
	 * @var array<string>
	 */
	public array $dirtyFieldsBefore = [];

	/**
	 * @var array<string>
	 */
	public array $dirtyFieldsAfter = [];

	/**
	 * @param \Cake\Event\EventInterface $event
	 * @param \Cake\Datasource\EntityInterface $entity
	 * @param \ArrayObject $options
	 *
	 * @return void
	 */
	public function beforeSave(EventInterface $event, EntityInterface $entity, ArrayObject $options): void {
		$this->dirtyFieldsBefore = $entity->getDirty();
	}

	/**
	 * @param \Cake\Event\EventInterface $event
	 * @param \Cake\Datasource\EntityInterface $entity
	 * @param \ArrayObject $options
	 *
	 * @return void
	 */
	public function afterSave(EventInterface $event, EntityInterface $entity, ArrayObject $options): void {
		$this->dirtyFieldsAfter = $entity->getDirty();
	}
}
