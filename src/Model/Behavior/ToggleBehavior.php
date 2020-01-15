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
	 * @var array
	 */
	protected $_defaultConfig = [
		'field' => 'primary',
		'on' => 'afterSave', // afterSave (without transactions) or beforeSave (with transactions)
		'scopeFields' => [],
		'scope' => [],
		'findOrder' => null, // null = autodetect modified/created
	];

	/**
	 * @param \Cake\Event\EventInterface $event
	 * @param \Cake\Datasource\EntityInterface $entity
	 * @param \ArrayObject $options
	 *
	 * @return void
	 */
	public function afterDelete(EventInterface $event, EntityInterface $entity, ArrayObject $options) {
		$field = $this->getConfig('field');

		$value = $entity->get($field);
		if (!$value) {
			return;
		}

		$conditions = $this->buildConditions($entity);

		$order = $this->getConfig('findOrder');
		if ($order === null) {
			$order = [];
			if ($this->_table->getSchema()->getColumn('modified')) {
				$order['modified'] = 'DESC';
			}
		}
		$entity = $this->_table->find()->where($conditions)->order($order)->first();
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
	 * @return void
	 * @throws \LogicException
	 */
	public function beforeSave(EventInterface $event, EntityInterface $entity, ArrayObject $options) {
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
		if (!$value && !$this->getCurrent($entity)) {
			// This should be caught with a validation rule as this is not normal behavior
			throw new LogicException();
		}

		$this->removeFromOthers($entity);
	}

	/**
	 * @param \Cake\Event\EventInterface $event
	 * @param \Cake\Datasource\EntityInterface $entity
	 * @param \ArrayObject $options
	 *
	 * @return void
	 *
	 * @throws \LogicException
	 */
	public function afterSave(EventInterface $event, EntityInterface $entity, ArrayObject $options) {
		if ($this->_config['on'] !== 'afterSave') {
			return;
		}

		if (!$entity->isDirty($this->getConfig('field'))) {
			return;
		}

		$value = $entity->get($this->getConfig('field'));
		if (!$value && !$this->getCurrent($entity)) {
			// This should be caught with a validation rule as this is not normal behavior
			throw new LogicException();
		}

		$this->removeFromOthers($entity);
	}

	/**
	 * @param \Cake\Datasource\EntityInterface $entity
	 *
	 * @return \Cake\Datasource\EntityInterface|null
	 */
	protected function getCurrent(EntityInterface $entity) {
		$conditions = $this->buildConditions($entity);

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
