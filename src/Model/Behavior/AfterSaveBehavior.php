<?php
/**
 * @author Mark Scherer
 * @license http://opensource.org/licenses/mit-license.php MIT
 */

namespace Tools\Model\Behavior;

use ArrayObject;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\ORM\Behavior;
use LogicException;

/**
 * Allow entity to be available inside afterSave() callback.
 * It takes a clone of the entity from beforeSave(). This allows all the
 * info on it to be available after save without resetting (dirty, ...).
 */
class AfterSaveBehavior extends Behavior {

	/**
	 * @var \Cake\Datasource\EntityInterface|null
	 */
	protected $_entity;

	/**
	 * @var array
	 */
	protected $_defaultConfig = [
	];

	/**
	 * @param \Cake\Event\EventInterface $event
	 * @param \Cake\Datasource\EntityInterface $entity
	 * @param \ArrayObject $options
	 * @return void
	 */
	public function beforeSave(EventInterface $event, EntityInterface $entity, ArrayObject $options) {
		$this->_entity = clone $entity;
	}

	/**
	 * @return \Cake\Datasource\EntityInterface
	 * @throws \LogicException
	 */
	public function getEntityBeforeSave() {
		if (!$this->_entity) {
			throw new LogicException('You need to successfully save first - including beforeSave() callback fired.');
		}

		return $this->_entity;
	}

}
