<?php

namespace Tools\Model\Table;

use Cake\ORM\Table as CakeTable;
use Cake\Validation\Validator;
use Cake\Utility\Inflector;
use Cake\Core\Configure;

class Table extends CakeTable {

	/**
	 * initialize()
	 *
	 * @param mixed $config
	 * @return void
	 */
	public function initialize(array $config) {
		// Shims
		$this->_shimRelations();

		$this->addBehavior('Timestamp');
	}

	/**
	 * Shim the 2.x way of class properties for relations.
	 *
	 * @return void
	 */
	protected function _shimRelations() {
		if (!empty($this->belongsTo)) {
			foreach ($this->belongsTo as $k => $v) {
				if (is_int($k)) {
					$k = $v;
					$v = array();
				}
				if (!empty($v['className'])) {
					$v['className'] = Inflector::pluralize($v['className']);
				}
				$v = array_filter($v);
				$this->belongsTo(Inflector::pluralize($k), $v);
			}
		}
		if (!empty($this->hasOne)) {
			foreach ($this->hasOne as $k => $v) {
				if (is_int($k)) {
					$k = $v;
					$v = array();
				}
				if (!empty($v['className'])) {
					$v['className'] = Inflector::pluralize($v['className']);
				}
				$v = array_filter($v);
				$this->hasOne(Inflector::pluralize($k), $v);
			}
		}
		if (!empty($this->hasMany)) {
			foreach ($this->hasMany as $k => $v) {
				if (is_int($k)) {
					$k = $v;
					$v = array();
				}
				if (!empty($v['className'])) {
					$v['className'] = Inflector::pluralize($v['className']);
				}
				$v = array_filter($v);
				$this->hasMany(Inflector::pluralize($k), $v);
			}
		}
		if (!empty($this->hasAndBelongsToMany)) {
			foreach ($this->hasAndBelongsToMany as $k => $v) {
				if (is_int($k)) {
					$k = $v;
					$v = array();
				}
				if (!empty($v['className'])) {
					$v['className'] = Inflector::pluralize($v['className']);
				}
				$v = array_filter($v);
				$this->belongsToMany(Inflector::pluralize($k), $v);
			}
		}
	}

	/**
	 * Shim the 2.x way of validate class properties.
	 *
	 * @param Validator $validator
	 * @return Validator
	 */
	public function validationDefault(Validator $validator) {
		if (!empty($this->validate)) {
			foreach ($this->validate as $k => $v) {
				if (is_int($k)) {
					$k = $v;
					$v = array();
				}
				if (isset($v['required'])) {
					$validator->validatePresence($k, $v['required']);
					unset($v['required']);
				}
				if (isset($v['allowEmpty'])) {
					$validator->allowEmpty($k, $v['allowEmpty']);
					unset($v['allowEmpty']);
				}
				$validator->add($k, $v);
			}
		}

		return $validator;
	}

	/**
	 * Shim to provide 2.x way of find('first').
	 *
	 * @param string $type
	 * @param array $options
	 * @return Query
	 */
	public function find($type = 'all', $options = []) {
		if ($type === 'first') {
			return parent::find('all', $options)->first();
		}
		return parent::find($type, $options);
	}

	/**
	 * Table::field()
	 *
	 * @param string $name
	 * @param array $options
	 * @return mixed Field value or null if not available
	 */
	public function field($name, array $options = array()) {
		$result = $this->find('all', $options)->first();
		if (!$result) {
			return null;
		}
		return $result->get($name);
	}

	/**
	 * Overwrite to allow markNew => auto
	 *
	 * @param array $data The data to build an entity with.
	 * @param array $options A list of options for the object hydration.
	 * @return \Cake\Datasource\EntityInterface
	 */
	public function newEntity(array $data = [], array $options = []) {
		$options += ['markNew' => Configure::read('Entity.autoMarkNew') ? 'auto' : null];
		if (isset($options['markNew']) && $options['markNew'] === 'auto') {
			$this->_primaryKey = (array)$this->primaryKey();
			$this->_primaryKey = $this->_primaryKey[0];
			$options['markNew'] = !empty($data[$this->_primaryKey]);
		}
		return parent::newEntity($data, $options);
	}

	/**
	 * truncate()
	 *
	 * @return void
	 */
	public function truncate() {
		$sql = $this->schema()->truncateSql($this->_connection);
		foreach ($sql as $snippet) {
			$this->_connection->execute($snippet);
		}
	}

}
