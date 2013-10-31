<?php
App::uses('ModelBehavior', 'Model');

/**
 * Allow Sort up/down of records.
 *
 * Expects a sort field to be present. This field will be sorted DESC.
 * The higher the sort value, the higher the record in the list.
 * You can also reverse the direction.
 *
 * Natural (default) order:
 * The sort value of new records is 0. This should be used in combination with
 * a secondary and possibly unique sort value for collisions around 0.
 *
 * Reversed order:
 * The sort value of a new record will be calculated (currently highest + 1).
 *
 * @cakephp 2.x
 * @author Mark Scherer
 * @license MIT
 */
class SortableBehavior extends ModelBehavior {

	protected $_defaults = array(
		'field' => 'sort',
		'reverse' => false // To make 0 the "highest" value
	);

	/**
	 * SortableBehavior::setup()
	 *
	 * @param Model $Model
	 * @param mixed $settings
	 * @return void
	 */
	public function setup(Model $Model, $settings = array()) {
		if (!isset($this->settings[$Model->alias])) {
			$this->settings[$Model->alias] = $this->_defaults;
		}
		$this->settings[$Model->alias] = array_merge(
		$this->settings[$Model->alias], (array)$settings);
	}

	/**
	 * SortableBehavior::beforeSave()
	 *
	 * @param Model $Model
	 * @param mixed $options
	 * @return void
	 */
	public function beforeSave(Model $Model, $options = array()) {
		if ($Model->id === false && isset($Model->data[$Model->alias]) &&
			!isset($Model->data[$Model->alias][$this->settings[$Model->alias]['field']])) {
			$sort = $this->_determineNextSortValue($Model);
			$Model->data[$Model->alias][$this->settings[$Model->alias]['field']] = $sort;
		}

		return true;
	}

	/**
	 * SortableBehavior::_determineNextSortValue()
	 *
	 * @param Model $Model
	 * @return int Sort value.
	 */
	protected function _determineNextSortValue(Model $Model) {
		if (empty($this->settings[$Model->alias]['reverse'])) {
			return 0;
		}
		$sort = $Model->find('first', array(
			'fields' => array(
				$this->settings[$Model->alias]['field']
			),
			'order' => array(
				$this->settings[$Model->alias]['field'] => 'DESC'
			)
		));
		if (!empty($sort)) {
			$sort = $sort[$Model->alias][$this->settings[$Model->alias]['field']];
			$sort++;
		} else {
			$sort = 1;
		}
		return $sort;
	}

	/**
	 * @return boolean Success
	 */
	public function moveUp(Model $Model, $id, $steps = 1) {
		return $this->_moveUpDown($Model, 'up', $id, $steps);
	}

	/**
	 * @return boolean Success
	 */
	public function moveDown(Model $Model, $id, $steps = 1) {
		return $this->_moveUpDown($Model, 'down', $id, $steps);
	}

	/**
	 * @param Model $Model
	 * @param string $direction
	 * @param int $steps Steps to jump. Defaults to 1.
	 * @return boolean Success
	 */
	protected function _moveUpDown(Model $Model, $direction, $id, $steps = 1) {
		// FIXME: Sort over more than one placement.
		if ($direction === 'down' && empty($this->settings[$Model->alias]['reverse'])) {
			$order = '<=';
			$findOrder = 'DESC';
		} else {
			$order = '>=';
			$findOrder = 'ASC';
		}
		$sort = $Model->find('list', array(
			'fields' => array(
				$this->settings[$Model->alias]['field']
			),
			'conditions' => array(
				'id' => $id
			)
		));
		if (empty($sort)) {
			return false;
		}
		list($sort) = array_values($sort);
		$data = $Model->find('list', array(
			'fields' => array(
				'id',
				$this->settings[$Model->alias]['field']
			),
			'conditions' => array(
				$this->settings[$Model->alias]['field'] . ' ' . $order => $sort
			),
			'order' => array(
				$this->settings[$Model->alias]['field'] => $findOrder
			),
			'limit' => $steps + 1
		));
		$value = end($data);
		$key = key($data);
		if ($key == $id) {
			return;
		}
		$lastId = $Model->id;
		if ($sort == $value) {
			if ($direction === 'down' && empty($this->settings[$Model->alias]['reverse'])) {
				$value++;
			} else {
				$value--;
			}
		}
		$Model->id = $key;
		$Model->saveField($this->settings[$Model->alias]['field'], $sort);
		$Model->id = $id;
		$Model->saveField($this->settings[$Model->alias]['field'], $value);
		$Model->id = $lastId;
		return true;
	}

}
