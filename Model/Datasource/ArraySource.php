<?php
/**
 * Array Datasource
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         CakePHP Datasources v 0.3
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

App::uses('Hash', 'Utility');
App::uses('ConnectionManager', 'Model');

/**
 * Array Datasource
 *
 * Datasource for array based models
 */
class ArraySource extends DataSource {

	/**
	 * Description string for this Data Source.
	 *
	 * @var string
	 */
	public $description = 'Array Datasource';

	/**
	 * List of requests ("queries")
	 *
	 * @var array
	 */
	protected $_requestsLog = array();

	/**
	 * Base Config
	 *
	 * @var array
	 */
	protected $_baseConfig = array(
		'driver' => '' // Just to avoid DebugKit warning
	);

	/**
	 * Start quote
	 *
	 * @var string
	 */
	public $startQuote = null;

	/**
	 * End quote
	 *
	 * @var string
	 */
	public $endQuote = null;

	/**
	 * Imitation of DboSource method.
	 *
	 * @param mixed $data Either a string with a column to quote. An array of columns
	 *   to quote.
	 * @return string SQL field
	 */
	public function name($data) {
		if (is_object($data) && isset($data->type)) {
			return $data->value;
		}
		if ($data === '*') {
			return '*';
		}
		if (is_array($data)) {
			foreach ($data as $i => $dataItem) {
				$data[$i] = $this->name($dataItem);
			}
			return $data;
		}
		return (string)$data;
	}

	/**
	 * Returns a Model description (metadata) or null if none found.
	 *
	 * @param Model $model
	 * @return array Show only id
	 */
	public function describe($model) {
		return array('id' => array());
	}

	/**
	 * List sources
	 *
	 * @param mixed $data
	 * @return boolean Always false. It's not supported
	 */
	public function listSources($data = null) {
		return false;
	}

	/**
	 * Used to read records from the Datasource. The "R" in CRUD
	 *
	 * @param Model $model The model being read.
	 * @param array $queryData An array of query data used to find the data you want
	 * @param null $recursive
	 * @return mixed
	 */
	public function read(Model $model, $queryData = array(), $recursive = null) {
		if (!isset($model->records) || !is_array($model->records) || empty($model->records)) {
			$this->_requestsLog[] = array(
				'query' => 'Model ' . $model->alias,
				'error' => __('No records found in model.'),
				'affected' => 0,
				'numRows' => 0,
				'took' => 0
			);
			return array($model->alias => array());
		}
		$startTime = microtime(true);
		$data = array();
		$i = 0;
		$limit = false;
		if ($recursive === null && isset($queryData['recursive'])) {
			$recursive = $queryData['recursive'];
		}

		if ($recursive !== null) {
			$_recursive = $model->recursive;
			$model->recursive = $recursive;
		}

		if (is_int($queryData['limit']) && $queryData['limit'] > 0) {
			$limit = $queryData['page'] * $queryData['limit'];
		}

		foreach ($model->records as $pos => $record) {
			// Tests whether the record will be chosen
			if (!empty($queryData['conditions'])) {
				$queryData['conditions'] = (array)$queryData['conditions'];
				if (!$this->conditionsFilter($model, $record, $queryData['conditions'])) {
					continue;
				}
			}
			$data[$i][$model->alias] = $record;
			$i++;
			// Test limit
			if ($limit !== false && $i == $limit && empty($queryData['order'])) {
				break;
			}
		}
		if ($queryData['fields'] === 'COUNT') {
			$this->_registerLog($model, $queryData, microtime(true) - $startTime, 1);
			if ($limit !== false) {
				$data = array_slice($data, ($queryData['page'] - 1) * $queryData['limit'], $queryData['limit'], false);
			}
			return array(array(array('count' => count($data))));
		}
		// Order
		if (!empty($queryData['order'])) {
			if (is_string($queryData['order'][0])) {
				$field = $queryData['order'][0];
				$alias = $model->alias;
				if (strpos($field, '.') !== false) {
					list($alias, $field) = explode('.', $field, 2);
				}
				if ($alias === $model->alias) {
					$sort = 'ASC';
					if (strpos($field, ' ') !== false) {
						list($field, $sort) = explode(' ', $field, 2);
					}
					if ($data) {
						$data = Hash::sort($data, '{n}.' . $model->alias . '.' . $field, $sort);
					}
				}
			}
		}
		// Limit
		if ($limit !== false) {
			$data = array_slice($data, ($queryData['page'] - 1) * $queryData['limit'], $queryData['limit'], false);
		}
		// Filter fields
		if (!empty($queryData['fields'])) {
			$listOfFields = array();
			foreach ((array)$queryData['fields'] as $field) {
				if (strpos($field, '.') !== false) {
					list($alias, $field) = explode('.', $field, 2);
					if ($alias !== $model->alias) {
						continue;
					}
				}
				$listOfFields[] = $field;
			}
			foreach ($data as $id => $record) {
				foreach ($record[$model->alias] as $field => $value) {
					if (!in_array($field, $listOfFields)) {
						unset($data[$id][$model->alias][$field]);
					}
				}
			}
		}
		$this->_registerLog($model, $queryData, microtime(true) - $startTime, count($data));
		$associations = $model->_associations;
		if ($model->recursive > -1) {
			foreach ($associations as $type) {
				foreach ($model->{$type} as $assoc => $assocData) {
					$linkModel = $model->{$assoc};

					if ($model->useDbConfig == $linkModel->useDbConfig) {
						$db = $this;
					} else {
						$db = ConnectionManager::getDataSource($linkModel->useDbConfig);
					}

					if (isset($db)) {
						if (method_exists($db, 'queryAssociation')) {
							$stack = array($assoc);
							$db->queryAssociation($model, $linkModel, $type, $assoc, $assocData, $queryData, true, $data, $model->recursive - 1, $stack);
						}
						unset($db);
					}

				}
			}
		}

		if ($recursive !== null) {
			$model->recursive = $_recursive;
		}
		return $data;
	}

	/**
	 * Conditions Filter
	 *
	 * @param Model $model
	 * @param string $record
	 * @param array $conditions
	 * @param boolean $or
	 * @return boolean
	 */
	public function conditionsFilter(Model $model, $record, $conditions, $or = false) {
		foreach ($conditions as $field => $value) {
			$return = null;
			if ($value === '') {
				continue;
			}
			if (is_array($value) && in_array(strtoupper($field), array('AND', 'NOT', 'OR'))) {
				switch (strtoupper($field)) {
					case 'AND':
						$return = $this->conditionsFilter($model, $record, $value);
						break;
					case 'NOT':
						$return = !$this->conditionsFilter($model, $record, $value);
						break;
					case 'OR':
						$return = $this->conditionsFilter($model, $record, $value, true);
						break;
				}
			} else {
				if (is_array($value)) {
					$type = 'IN';
				} elseif (preg_match('/^(\w+\.?\w+)\s+(=|!=|LIKE|IN|<|<=|>|>=)\s*$/i', $field, $matches)) {
					$field = $matches[1];
					$type = strtoupper($matches[2]);
				} elseif (preg_match('/^(\w+\.?\w+)\s+(=|!=|LIKE|IN|<|<=|>|>=)\s+(.*)$/i', $value, $matches)) {
					$field = $matches[1];
					$type = strtoupper($matches[2]);
					$value = $matches[3];
				} else {
					$type = '=';
				}
				if (strpos($field, '.') !== false) {
					list($alias, $field) = explode('.', $field, 2);
					if ($alias != $model->alias) {
						continue;
					}
				}
				switch ($type) {
					case '<':
						$return = (array_key_exists($field, $record) && $record[$field] < $value);
						break;
					case '<=':
						$return = (array_key_exists($field, $record) && $record[$field] <= $value);
						break;
					case '=':
						$return = (array_key_exists($field, $record) && $record[$field] == $value);
						break;
					case '>':
						$return = (array_key_exists($field, $record) && $record[$field] > $value);
						break;
					case '>=':
						$return = (array_key_exists($field, $record) && $record[$field] >= $value);
						break;
					case '!=':
						$return = (!array_key_exists($field, $record) || $record[$field] != $value);
						break;
					case 'LIKE':
						$value = preg_replace(array('#(^|[^\\\\])_#', '#(^|[^\\\\])%#'), array('$1.', '$1.*'), $value);
						$return = (isset($record[$field]) && preg_match('#^' . $value . '$#i', $record[$field]));
						break;
					case 'IN':
						$items = array();
						if (is_array($value)) {
							$items = $value;
						} elseif (preg_match('/^\(\w+(,\s*\w+)*\)$/', $value)) {
							$items = explode(',', trim($value, '()'));
							$items = array_map('trim', $items);
						}
						$return = (array_key_exists($field, $record) && in_array($record[$field], (array)$items));
						break;
				}
			}
			if ($return === $or) {
				return $or;
			}
		}
		return !$or;
	}

	/**
	 * Returns an calculation
	 *
	 * @param model $model
	 * @param string $type Lowercase name type, i.e. 'count' or 'max'
	 * @param array $params Function parameters (any values must be quoted manually)
	 * @return string Calculation method
	 */
	public function calculate(Model $model, $type, $params = array()) {
		return 'COUNT';
	}

	/**
	 * Implemented to make the datasource work with Model::find('count').
	 *
	 * @return boolean Always false;
	 */
	public function expression() {
		return false;
	}

	/**
	 * Queries associations. Used to fetch results on recursive models.
	 *
	 * @param Model $model Primary Model object
	 * @param Model $linkModel Linked model that
	 * @param string $type Association type, one of the model association types ie. hasMany
	 * @param string $association The name of the association
	 * @param array $assocData The data about the association
	 * @param array $queryData
	 * @param boolean $external Whether or not the association query is on an external datasource.
	 * @param array $resultSet Existing results
	 * @param integer $recursive Number of levels of association
	 * @param array $stack
	 */
	public function queryAssociation(Model $model, Model $linkModel, $type, $association, $assocData, &$queryData, $external, &$resultSet, $recursive, $stack) {
		$assocData = array_merge(array('conditions' => null, 'fields' => null, 'order' => null), $assocData);
		if (isset($queryData['fields'])) {
			$assocData['fields'] = array_filter(array_merge((array)$queryData['fields'], (array)$assocData['fields']));
		}
		if (isset($queryData['conditions'])) {
			$assocData['conditions'] = array_filter(array_merge((array)$queryData['conditions'], (array)$assocData['conditions']));
		}
		$query = array(
			'fields' => array_filter((array)$assocData['fields']),
			'conditions' => array_filter((array)$assocData['conditions']),
			'group' => null,
			'order' => $assocData['order'],
			'limit' => isset($assocData['limit']) ? $assocData['limit'] : null,
			'page' => 1,
			'offset' => null,
			'callbacks' => true,
			'recursive' => $recursive === 0 ? -1 : $recursive
		);
		foreach ($resultSet as &$record) {
			$data = array();
			if ($type === 'belongsTo') {
				if (isset($record[$model->alias][$assocData['foreignKey']])) {
					$conditions = array_merge($query['conditions'], array($linkModel->alias . '.' . $linkModel->primaryKey => $record[$model->alias][$assocData['foreignKey']]));
					$limit = 1;
					$data = $this->read($linkModel, compact('conditions', 'limit') + $query);
				}
			} elseif (($type === 'hasMany' || $type === 'hasOne') && $model->recursive > 0) {
				$conditions = array_merge($query['conditions'], array($linkModel->alias . '.' . $assocData['foreignKey'] => $record[$model->alias][$model->primaryKey]));
				$limit = $type === 'hasOne' ? 1 : $query['limit'];
				$data = $this->read($linkModel, compact('conditions', 'limit') + $query);
			} elseif ($type === 'hasAndBelongsToMany' && $model->recursive > 0) {
				$joinModel = ClassRegistry::init($assocData['with']);
				$fields = array($joinModel->alias . '.' . $assocData['associationForeignKey']);
				$conditions = array($joinModel->alias . '.' . $assocData['foreignKey'] => $record[$model->alias][$model->primaryKey]);
				$recursive = -1;
				$ids = $joinModel->getDataSource()->read($joinModel, compact('fields', 'conditions', 'recursive') + $query);
				if ($ids) {
					$ids = Hash::extract($ids, "{n}.{$joinModel->alias}.{$assocData['associationForeignKey']}");
					$conditions = array_merge($query['conditions'], array($linkModel->alias . '.' . $linkModel->primaryKey => $ids));
					$data = $this->read($linkModel, compact('conditions') + $query);
				}
			} else {
				continue;
			}

			if (!$data) {
				$record += array($linkModel->alias => array());
				continue;
			}

			$formatted = array();
			foreach ($data as $associated) {
				foreach ($associated as $modelName => $associatedData) {
					if ($modelName === $linkModel->alias) {
						continue;
					}
					$associated[$linkModel->alias][$modelName] = $associatedData;
					unset($associated[$modelName]);
				}
				$formatted[] = $associated;
			}

			if ($type === 'hasOne' || $type === 'belongsTo') {
				$record += array($linkModel->alias => $formatted[0][$linkModel->alias]);
				continue;
			}
			$record += array($linkModel->alias => Hash::extract($formatted, "{n}.{$linkModel->alias}"));
		}
	}

	/**
	 * Get the query log as an array.
	 *
	 * @param boolean $sorted Get the queries sorted by time taken, defaults to false.
	 * @param boolean $clear Clear after return logs
	 * @return array Array of queries run as an array
	 */
	public function getLog($sorted = false, $clear = true) {
		if ($sorted) {
			$log = sortByKey($this->_requestsLog, 'took', 'desc', SORT_NUMERIC);
		} else {
			$log = $this->_requestsLog;
		}
		if ($clear) {
			$this->_requestsLog = array();
		}
		return array('log' => $log, 'count' => count($log), 'time' => array_sum(Hash::extract($log, '{n}.took')));
	}

	/**
	 * Generate a log registry
	 *
	 * @param Model $model
	 * @param array $queryData
	 * @param float $took
	 * @param integer $numRows
	 * @return void
	 */
	protected function _registerLog(Model $model, &$queryData, $took, $numRows) {
		if (!Configure::read('debug')) {
			return;
		}
		$this->_requestsLog[] = array(
			'query' => $this->_pseudoSelect($model, $queryData),
			'error' => '',
			'affected' => 0,
			'numRows' => $numRows,
			'took' => round($took, 3)
		);
	}

	/**
	 * Generate a pseudo select to log
	 *
	 * @param Model $model Model
	 * @param array $queryData Query data sent by find
	 * @return string Pseudo query
	 */
	protected function _pseudoSelect(Model $model, &$queryData) {
		$out = '(symbolic) SELECT ';
		if (empty($queryData['fields'])) {
			$out .= '*';
		} elseif ($queryData['fields']) {
			$out .= 'COUNT(*)';
		} else {
			$out .= implode(', ', $queryData['fields']);
		}
		$out .= ' FROM ' . $model->alias;
		if (!empty($queryData['conditions'])) {
			$out .= ' WHERE';
			foreach ($queryData['conditions'] as $id => $condition) {
				if (empty($condition)) {
					continue;
				}
				if (is_array($condition)) {
					$condition = '(' . implode(', ', $condition) . ')';
					if (strpos($id, ' ') === false) {
						$id .= ' IN';
					}
				}
				if (is_string($id)) {
					if (strpos($id, ' ') !== false) {
						$condition = $id . ' ' . $condition;
					} else {
						$condition = $id . ' = ' . $condition;
					}
				}
				if (preg_match('/^(\w+\.)?\w+ /', $condition, $matches)) {
					if (!empty($matches[1]) && substr($matches[1], 0, -1) !== $model->alias) {
						continue;
					}
				}
				$out .= ' (' . $condition . ') &&';
			}
			$out = substr($out, 0, -3);
		}
		if (!empty($queryData['order'][0])) {
			$order = $queryData['order'];
			if (is_array($order[0])) {
				$new = array();
				foreach ($order[0] as $field => $direction) {
					$new[] = "$field $direction";
				}
				$order = $new;
			}
			$out .= ' ORDER BY ' . implode(', ', $order);
		}
		if (!empty($queryData['limit'])) {
			$out .= ' LIMIT ' . (($queryData['page'] - 1) * $queryData['limit']) . ', ' . $queryData['limit'];
		}
		return $out;
	}
}
