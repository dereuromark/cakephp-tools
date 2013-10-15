<?php

/**
 * Copyright 2007-2010, Cake Development Corporation (http://cakedc.com)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright 2007-2010, Cake Development Corporation (http://cakedc.com)
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
App::uses('ModelBehavior', 'Model');

/**
 * Soft Delete Behavior
 *
 * Note: To make delete() return true with SoftDelete attached, you need to modify your AppModel and overwrite
 * delete() there:
 *
 * public function delete($id = null, $cascade = true) {
 *   $result = parent::delete($id, $cascade);
 *   if (!$result && $this->Behaviors->loaded('SoftDelete')) {
 *     return $this->softDeleted;
 *   }
 *   return $result;
 * }
 *
 */
class SoftDeleteBehavior extends ModelBehavior {

	/**
	 * Default settings
	 *
	 * @var array
	 */
	protected $_defaults = array(
		'attribute' => 'softDeleted',
		'fields' => array(
			'deleted' => 'deleted_date'
		)
	);

	/**
	 * Holds activity flags for models
	 *
	 * @var array
	 */
	public $runtime = array();

	/**
	 * Setup callback
	 *
	 * @param Model $model
	 * @param array $settings
	 * @return void
	 */
	public function setup(Model $model, $settings = array()) {
		$settings = array_merge($this->_defaults, $settings);

		$error = 'SoftDeleteBehavior::setup(): model ' . $model->alias . ' has no field ';
		$fields = $this->_normalizeFields($model, $settings['fields']);
		foreach ($fields as $flag => $date) {
			if ($model->hasField($flag)) {
				if ($date && !$model->hasField($date)) {
					trigger_error($error . $date, E_USER_NOTICE);
					return;
				}
				continue;
			}
			trigger_error($error . $flag, E_USER_NOTICE);
			return;
		}

		$this->settings[$model->alias] = array_merge($settings, array('fields' => $fields));
		$this->softDelete($model, true);

		$attribute = $this->settings[$model->alias]['attribute'];
		$model->$attribute = false;
	}

	/**
	 * Before find callback
	 *
	 * @param Model $model
	 * @param array $query
	 * @return array
	 */
	public function beforeFind(Model $model, $query) {
		$runtime = $this->runtime[$model->alias];
		if ($runtime) {
			if (!is_array($query['conditions'])) {
				$query['conditions'] = array();
			}
			$conditions = array_filter(array_keys($query['conditions']));

			$fields = $this->_normalizeFields($model);

			foreach ($fields as $flag => $date) {
				if ($runtime === true || $flag === $runtime) {
					if (!in_array($flag, $conditions) && !in_array($model->name . '.' . $flag, $conditions)) {
						$query['conditions'][$model->alias . '.' . $flag] = false;
					}

					if ($flag === $runtime) {
						break;
					}
				}
			}
			return $query;
		}
	}

	/**
	 * Before delete callback
	 *
	 * @param Model $model
	 * @param array $query
	 * @return boolean Success
	 */
	public function beforeDelete(Model $model, $cascade = true) {
		$runtime = $this->runtime[$model->alias];
		if ($runtime) {
			if ($this->delete($model, $model->id)) {
				$attribute = $this->settings[$model->alias]['attribute'];
				$model->$attribute = true;
			}
			return false;
		}
		return true;
	}

	/**
	 * Mark record as deleted
	 *
	 * @param Model $model
	 * @param integer $id
	 * @return boolean Success
	 */
	public function delete(Model $model, $id) {
		$runtime = $this->runtime[$model->alias];

		$data = array();
		$fields = $this->_normalizeFields($model);
		foreach ($fields as $flag => $date) {
			if ($runtime === true || $flag === $runtime) {
				$data[$flag] = true;
				if ($date) {
					$data[$date] = date('Y-m-d H:i:s');
				}
				if ($flag === $runtime) {
					break;
				}
			}
		}

		$model->create();
		$model->set($model->primaryKey, $id);
		return (bool)$model->save(array($model->alias => $data), false, array_keys($data));
	}

	/**
	 * Mark record as not deleted
	 *
	 * @param Model $model
	 * @param integer $id
	 * @return boolean Success
	 */
	public function undelete(Model $model, $id) {
		$runtime = $this->runtime[$model->alias];
		$this->softDelete($model, false);

		$data = array();
		$fields = $this->_normalizeFields($model);
		foreach ($fields as $flag => $date) {
			if ($runtime === true || $flag === $runtime) {
				$data[$flag] = false;
				if ($date) {
					$data[$date] = null;
				}
				if ($flag === $runtime) {
					break;
				}
			}
		}

		$model->create();
		$model->set($model->primaryKey, $id);
		$result = $model->save(array($model->alias => $data), false, array_keys($data));
		$this->softDelete($model, $runtime);
		return $result;
	}

	/**
	 * Enable/disable SoftDelete functionality
	 *
	 * Usage from model:
	 * $this->softDelete(false); deactivate this behavior for model
	 * $this->softDelete('field_two'); enabled only for this flag field
	 * $this->softDelete(true); enable again for all flag fields
	 * $config = $this->softDelete(null); for obtaining current setting
	 *
	 * @param Model $model
	 * @param mixed $active
	 * @return mixed If $active is null, then current setting/null, or boolean if runtime setting for model was changed
	 */
	public function softDelete(Model $model, $active) {
		if ($active === null) {
			return isset($this->runtime[$model->alias]) ? $this->runtime[$model->alias] : null;
		}

		$result = !isset($this->runtime[$model->alias]) || $this->runtime[$model->alias] !== $active;
		$this->runtime[$model->alias] = $active;
		$this->_softDeleteAssociations($model, $active);
		return $result;
	}

	/**
	 * Returns number of outdated softdeleted records prepared for purge
	 *
	 * @param Model $model
	 * @param mixed $expiration anything parseable by strtotime(), by default '-90 days'
	 * @return integer
	 */
	public function purgeDeletedCount(Model $model, $expiration = '-90 days') {
		$this->softDelete($model, false);
		return $model->find('count', array('conditions' => $this->_purgeDeletedConditions($model, $expiration), 'recursive' => -1));
	}

	/**
	 * Purge table
	 *
	 * @param Model $model
	 * @param mixed $expiration anything parseable by strtotime(), by default '-90 days'
	 * @return boolean If there were some outdated records
	 */
	public function purgeDeleted(Model $model, $expiration = '-90 days') {
		$this->softDelete($model, false);
		$records = $model->find('all', array(
			'conditions' => $this->_purgeDeletedConditions($model, $expiration),
			'fields' => array($model->primaryKey),
			'recursive' => -1));
		if ($records) {
			foreach ($records as $record) {
				$model->delete($record[$model->alias][$model->primaryKey]);
			}
			return true;
		}
		return false;
	}

	/**
	 * Returns conditions for finding outdated records
	 *
	 * @param Model $model
	 * @param mixed $expiration anything parseable by strtotime(), by default '-90 days'
	 * @return array
	 */
	protected function _purgeDeletedConditions(Model $model, $expiration = '-90 days') {
		$purgeDate = date('Y-m-d H:i:s', strtotime($expiration));
		$conditions = array();
		foreach ($this->settings[$model->alias]['fields'] as $flag => $date) {
			$conditions[$model->alias . '.' . $flag] = true;
			if ($date) {
				$conditions[$model->alias . '.' . $date . ' <'] = $purgeDate;
			}
		}
		return $conditions;
	}

	/**
	 * Return normalized field array
	 *
	 * @param Model $model
	 * @param array $settings
	 * @return array
	 */
	protected function _normalizeFields(Model $model, $settings = array()) {
		if (empty($settings)) {
			$settings = $this->settings[$model->alias]['fields'];
		}
		$result = array();
		foreach ($settings as $flag => $date) {
			if (is_numeric($flag)) {
				$flag = $date;
				$date = false;
			}
			$result[$flag] = $date;
		}
		return $result;
	}

	/**
	 * Modifies conditions of hasOne and hasMany associations.
	 *
	 * If multiple delete flags are configured for model, then $active=true doesn't
	 * do anything - you have to alter conditions in association definition
	 *
	 * @param Model $model
	 * @param mixed $active
	 * @return void
	 */
	protected function _softDeleteAssociations(Model $model, $active) {
		if (empty($model->belongsTo)) {
			return;
		}
		$fields = array_keys($this->_normalizeFields($model));
		$parentModels = array_keys($model->belongsTo);

		foreach ($parentModels as $parentModel) {
			foreach (array('hasOne', 'hasMany') as $assocType) {
				if (empty($model->{$parentModel}->{$assocType})) {
					continue;
				}

				foreach ($model->{$parentModel}->{$assocType} as $assoc => $assocConfig) {
					$modelName = !empty($assocConfig['className']) ? $assocConfig['className'] : $assoc;
					if ($model->alias !== $modelName) {
						continue;
					}

					$conditions = $model->{$parentModel}->{$assocType}[$assoc]['conditions'];
					if (!is_array($conditions)) {
						$model->{$parentModel}->{$assocType}[$assoc]['conditions'] = array();
					}

					$multiFields = 1 < count($fields);
					foreach ($fields as $field) {
						if ($active) {
							if (!isset($conditions[$field]) && !isset($conditions[$assoc . '.' . $field])) {
								if (is_string($active)) {
									if ($field === $active) {
										$conditions[$assoc . '.' . $field] = false;
									} elseif (isset($conditions[$assoc . '.' . $field])) {
										unset($conditions[$assoc . '.' . $field]);
									}
								} elseif (!$multiFields) {
									$conditions[$assoc . '.' . $field] = false;
								}
							}
						} elseif (isset($conditions[$assoc . '.' . $field])) {
							unset($conditions[$assoc . '.' . $field]);
						}
					}
				}
			}
		}
	}

}
