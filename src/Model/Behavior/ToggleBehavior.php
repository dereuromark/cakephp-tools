<?php

namespace Tools\Model\Behavior;

use ArrayObject;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\ORM\Behavior;
use LogicException;

/**
 * ToggleBehavior
 *
 * An implementation of a unique field toggle per table or scope.
 * This will ensure that on a set of records only one can be a "primary" one, setting the others to false then.
 * On delete it will give the primary status to another record if applicable.
 *
 * @author Mark Scherer
 * @license MIT
 */
class ToggleBehavior extends Behavior {

	/**
	 * Default config
	 *
	 * @var array<string, mixed>
	 */
	protected array $_defaultConfig = [
		'field' => 'primary',
		'on' => 'afterSave', // afterSave (without transactions) or beforeSave (with transactions)
		'scopeFields' => [],
		'scope' => [],
		'findOrder' => null, // null = autodetect modified/created, false to disable
	];

	/**
	 * @param \Cake\Event\EventInterface $event
	 * @param \Cake\Datasource\EntityInterface $entity
	 * @param \ArrayObject $options
	 *
	 * @return void
	 */
	public function afterDelete(EventInterface $event, EntityInterface $entity, ArrayObject $options): void {
		$field = $this->getConfig('field');

		$value = $entity->get($field);
		if (!$value) {
			return;
		}

		$conditions = $this->buildConditions($entity);

		$order = $this->getConfig('findOrder');
		if ($order === false) {
			return;
		}

		if ($order === null) {
			$order = [];
			if ($this->_table->getSchema()->getColumn('modified')) {
				$order['modified'] = 'DESC';
			}
		}
		/** @var \Cake\Datasource\EntityInterface|null $entity */
		$entity = $this->_table->find()->where($conditions)->orderBy($order)->first();
		if (!$entity) {
			// This should be caught with a validation rule if at least one "primary" must exist
			return;
		}

		$entity->set($field, true);
		$this->_table->saveOrFail($entity);
	}

	/**
	 * @param \Cake\Event\EventInterface $event
	 * @param \Cake\Datasource\EntityInterface $entity
	 * @param \ArrayObject $options
	 * @throws \LogicException
	 * @return void
	 */
	public function beforeSave(EventInterface $event, EntityInterface $entity, ArrayObject $options): void {
		$field = $this->getConfig('field');

		if ($entity->isNew() && !$entity->get($field)) {
			if (!$this->getCurrent($entity)) {
				$entity->set($field, true);
			}
		}

		if ($this->_config['on'] !== 'beforeSave') {
			return;
		}

		$value = $entity->get($this->getConfig('field'));

		// Only remove from others if this entity is being set to true
		// Don't remove existing defaults when adding a new non-default entity
		if ($value) {
			$this->removeFromOthers($entity);
		} elseif (!$this->getCurrent($entity)) {
			// This should be caught with a validation rule as this is not normal behavior
			throw new LogicException();
		}
	}

	/**
	 * @param \Cake\Event\EventInterface $event
	 * @param \Cake\Datasource\EntityInterface $entity
	 * @param \ArrayObject $options
	 *
	 * @throws \LogicException
	 * @return void
	 */
	public function afterSave(EventInterface $event, EntityInterface $entity, ArrayObject $options): void {
		if ($this->_config['on'] !== 'afterSave') {
			return;
		}

		if (!$entity->isDirty($this->getConfig('field'))) {
			return;
		}

		$value = $entity->get($this->getConfig('field'));

		// Only remove from others if this entity is being set to true
		// Don't remove existing defaults when adding a new non-default entity
		if ($value) {
			$this->removeFromOthers($entity);
		} elseif (!$this->getCurrent($entity)) {
			// This should be caught with a validation rule as this is not normal behavior
			throw new LogicException();
		}
	}

	/**
	 * @param \Cake\Datasource\EntityInterface $entity
	 *
	 * @return \Cake\Datasource\EntityInterface|null
	 */
	protected function getCurrent(EntityInterface $entity) {
		$conditions = $this->buildConditions($entity);

		/** @var \Cake\Datasource\EntityInterface|null */
		return $this->_table->find()
			->where($conditions)
			->first();
	}

	/**
	 * @param \Cake\Datasource\EntityInterface $entity
	 *
	 * @return void
	 */
	protected function removeFromOthers(EntityInterface $entity) {
		$field = $this->getConfig('field');
		$id = $entity->get('id');
		$conditions = $this->buildConditions($entity);
		$this->_table->updateAll([$field => false], ['id !=' => $id] + $conditions);
	}

	/**
	 * @param \Cake\Datasource\EntityInterface $entity
	 *
	 * @return array
	 */
	protected function buildConditions(EntityInterface $entity) {
		$conditions = $this->getConfig('scope');
		$scopeFields = (array)$this->getConfig('scopeFields');
		foreach ($scopeFields as $scopeField) {
			$conditions[$scopeField] = $entity->get($scopeField);
		}

		return $conditions;
	}

	/**
	 * @param \Cake\Datasource\EntityInterface $entity
	 *
	 * @return bool
	 */
	public function toggleField(EntityInterface $entity) {
		$field = $this->getConfig('field');
		$id = $entity->get('id');
		$conditions = $this->buildConditions($entity);

		$primary = $this->_table->updateAll([$field => true], ['id' => $id] + $conditions);
		$others = $this->_table->updateAll([$field => false], ['id !=' => $id] + $conditions);

		return $primary + $others > 0;
	}

}
