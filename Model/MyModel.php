<?php
App::uses('Model', 'Model');
App::uses('Utility', 'Tools.Utility');

/**
 * Model enhancements for Cake2
 *
 * @author Mark Scherer
 * @license MIT
 */
class MyModel extends Model {

	public $recursive = -1;

	public $actsAs = array('Containable');

	/**
	 * MyModel::__construct()
	 *
	 * @param integer $id
	 * @param string $table
	 * @param string $ds
	 */
	public function __construct($id = false, $table = null, $ds = null) {
		parent::__construct($id, $table, $ds);

		// enable caching
		if (!Configure::read('Cache.disable') && Cache::config('sql') === false) {
			if (!file_exists(CACHE . 'sql')) {
				mkdir(CACHE . 'sql', CHOWN_PUBLIC);
			}
			Cache::config('sql', array(
				'engine' => 'File',
				'serialize' => true,
				'prefix'	=> '',
				'path' => CACHE . 'sql' . DS,
				'duration'	=> '+1 day'
			));
		}
		if (!Configure::read('Model.disablePrefixing')) {
			$this->prefixOrderProperty();
		}

		// Get a notice if there is an AppModel instance instead of a real Model (in those cases usually a dev error!)
		if (!is_a($this, $this->name) && $this->displayField !== $this->primaryKey && $this->useDbConfig === 'default'
			&& !Configure::read('Core.disableModelInstanceNotice')) {
			trigger_error('AppModel instance! Expected: ' . $this->name);
		}
	}

	/**
	 * Prefixes the order property with the actual alias if its a string or array.
	 *
	 * The core fails on using the proper prefix when building the query with two
	 * different tables.
	 *
	 * @return void
	 */
	public function prefixOrderProperty() {
		if (is_string($this->order)) {
			$this->order = $this->prefixAlias($this->order);
		}
		if (is_array($this->order)) {
			foreach ($this->order as $key => $value) {
				if (is_numeric($key)) {
					$this->order[$key] = $this->prefixAlias($value);
				} else {
					$newKey = $this->prefixAlias($key);
					$this->order[$newKey] = $value;
					if ($newKey !== $key) {
						unset($this->order[$key]);
					}
				}
			}
		}
	}

	/**
	 * Checks if a string of a field name contains a dot if not it will add it and add the alias prefix.
	 *
	 * @param string
	 * @return string
	 */
	public function prefixAlias($string) {
		if (strpos($string, '.') === false) {
			return $this->alias . '.' . $string;
		}
		return $string;
	}

	/**
	 * Deconstructs a complex data type (array or object) into a single field value.
	 * BUGFIXED VERSION - autodetects type and allows manual override
	 *
	 * @param string $field The name of the field to be deconstructed
	 * @param array|object $data An array or object to be deconstructed into a field
	 * @return mixed The resulting data that should be assigned to a field
	 */
	public function deconstruct($field, $data, $type = null) {
		if (!is_array($data)) {
			return $data;
		}
		if ($type === null) {
			$type = $this->getColumnType($field);
		}
		if ($type === null) {
			//try to autodetect
			if (isset($data['day']) || isset($data['month']) || isset($data['year'])) {
				$type = 'date';
			}
			if (isset($data['hour']) || isset($data['min']) || isset($data['sec'])) {
				$type .= 'time';
			}
		}

		if (in_array($type, array('datetime', 'timestamp', 'date', 'time'))) {
			$useNewDate = (isset($data['year']) || isset($data['month']) ||
				isset($data['day']) || isset($data['hour']) || isset($data['minute']));

			$dateFields = array('Y' => 'year', 'm' => 'month', 'd' => 'day', 'H' => 'hour', 'i' => 'min', 's' => 'sec');
			$timeFields = array('H' => 'hour', 'i' => 'min', 's' => 'sec');
			$date = array();

			if (isset($data['meridian']) && empty($data['meridian'])) {
				return null;
			}

			if (
				isset($data['hour']) &&
				isset($data['meridian']) &&
				!empty($data['hour']) &&
				$data['hour'] != 12 &&
				'pm' == $data['meridian']
			) {
				$data['hour'] = $data['hour'] + 12;
			}
			if (isset($data['hour']) && isset($data['meridian']) && $data['hour'] == 12 && 'am' == $data['meridian']) {
				$data['hour'] = '00';
			}
			if ($type === 'time') {
				foreach ($timeFields as $key => $val) {
					if (!isset($data[$val]) || $data[$val] === '0' || $data[$val] === '00') {
						$data[$val] = '00';
					} elseif ($data[$val] !== '') {
						$data[$val] = sprintf('%02d', $data[$val]);
					}
					if (!empty($data[$val])) {
						$date[$key] = $data[$val];
					} else {
						return null;
					}
				}
			}

			if ($type === 'datetime' || $type === 'timestamp' || $type === 'date') {
				foreach ($dateFields as $key => $val) {
					if ($val === 'hour' || $val === 'min' || $val === 'sec') {
						if (!isset($data[$val]) || $data[$val] === '0' || $data[$val] === '00') {
							$data[$val] = '00';
						} else {
							$data[$val] = sprintf('%02d', $data[$val]);
						}
					}
					if (!isset($data[$val]) || isset($data[$val]) && (empty($data[$val]) || $data[$val][0] === '-')) {
						return null;
					}
					if (isset($data[$val]) && !empty($data[$val])) {
						$date[$key] = $data[$val];
					}
				}
			}

			if ($useNewDate && !empty($date)) {
				$format = $this->getDataSource()->columns[$type]['format'];
				foreach (array('m', 'd', 'H', 'i', 's') as $index) {
					if (isset($date[$index])) {
						$date[$index] = sprintf('%02d', $date[$index]);
					}
				}
				return str_replace(array_keys($date), array_values($date), $format);
			}
		}
		return $data;
	}

	/**
	 * The main method for any enumeration, should be called statically
	 * Now also supports reordering/filtering
	 *
	 * @link http://www.dereuromark.de/2010/06/24/static-enums-or-semihardcoded-attributes/
	 * @param string $value or array $keys or NULL for complete array result
	 * @param array $options (actual data)
	 * @return mixed string/array
	 */
	public static function enum($value, $options, $default = null) {
		if ($value !== null && !is_array($value)) {
			if (array_key_exists($value, $options)) {
				return $options[$value];
			}
			return $default;
		} elseif ($value !== null) {
			$newOptions = array();
			foreach ($value as $v) {
				$newOptions[$v] = $options[$v];
			}
			return $newOptions;
		}
		return $options;
	}

	/**
	 * @return string Error message with error number
	 */
	public function lastError() {
		$db = $this->getDataSource();
		return $db->lastError();
	}

	/**
	 * Combine virtual fields with fields values of find()
	 * USAGE:
	 * $this->Model->find('all', array('fields' => $this->Model->virtualFields('full_name')));
	 * Also adds the field to the virtualFields array of the model (for correct result)
	 * TODO: adding of fields only temperory!
	 *
	 * @param array $virtualFields to include
	 * @return array
	 */
	public function virtualFields($fields = array()) {
		$res = array();
		foreach ((array)$fields as $field => $sql) {
			if (is_int($field)) {
				$field = $sql;
				$sql = null;
			}
			$plugin = $model = null;
			if (($pos = strrpos($field, '.')) !== false) {
				$model = substr($field, 0, $pos);
				$field = substr($field, $pos + 1);

				if (($pos = strrpos($model, '.')) !== false) {
					list($plugin, $model) = pluginSplit($model);
				}
			}
			if (empty($model)) {
				$model = $this->alias;
				if ($sql === null) {
					$sql = $this->virtualFields[$field];
				} else {
					$this->virtualFields[$field] = $sql;
				}
			} else {
				if (!isset($this->$model)) {
					$fullModelName = ($plugin ? $plugin . '.' : '') . $model;
					$this->$model = ClassRegistry::init($fullModelName);
				}
				if ($sql === null) {
					$sql = $this->$model->virtualFields[$field];
				} else {
					$this->$model->virtualFields[$field] = $sql;
				}
			}
			$res[] = $sql . ' AS ' . $model . '__' . $field;
		}
		return $res;
	}

	/**
	 * HIGHLY EXPERIMENTAL
	 * manually escape value for updateAll() etc
	 *
	 * @return string
	 */
	public function escapeValue($value) {
		if ($value === null || is_numeric($value)) {
			return $value;
		}
		if (is_bool($value)) {
			return (int)$value;
		}
		return "'" . $value . "'";
	}

	/**
	 * HIGHLY EXPERIMENTAL
	 *
	 * @see http://cakephp.lighthouseapp.com/projects/42648/tickets/1799-model-should-have-escapefield-method
	 * @return string
	 */
	public function value($content) {
		$db = $this->getDatasource();
		return $db->value($content);
	}

	/**
	 * TODO: move to behavior (Incremental)
	 *
	 * @param mixed id (single string)
	 * @param options:
	 * - step (defaults to 1)
	 * - current (if none it will get it from db)
	 * - reset (if true, it will be set to 0)
	 * - field (defaults to 'count')
	 * - modify (if true if will affect modified timestamp)
	 * - timestampField (if provided it will be filled with NOW())
	 * @return See Model::save()
	 */
	public function up($id, $customOptions = array()) {
		$step = 1;
		if (isset($customOptions['step'])) {
				$step = $customOptions['step'];
		}
		$field = 'count';
		if (isset($customOptions['field'])) {
				$field = $customOptions['field'];
		}

		if (isset($customOptions['reset'])) {
			$currentValue = $step = 0;
		} elseif (!isset($customOptions['current'])) {
			$currentValue = $this->field($field, array($this->alias . '.id' => $id));
			if ($currentValue === false) {
				return false;
			}
		} else {
			$currentValue = $customOptions['current'];
		}

		$value = (int)$currentValue + (int)$step;
		$data = array($field => $value);
		if (empty($customOptions['modify'])) {
			$data['modified'] = false;
		}
		if (!empty($customOptions['timestampField'])) {
			$data[$customOptions['timestampField']] = date(FORMAT_DB_DATETIME);
		}
		$this->id = $id;
		return $this->save($data, false);
	}

	/**
	 * Return the next auto increment id from the current table
	 * UUIDs will return false
	 *
	 * @return integer next auto increment value or False on failure
	 */
	public function getNextAutoIncrement() {
		$query = "SHOW TABLE STATUS WHERE name = '" . $this->tablePrefix . $this->table . "'";
		$result = $this->query($query);
		if (!isset($result[0]['TABLES']['Auto_increment'])) {
			return false;
		}
		return (int)$result[0]['TABLES']['Auto_increment'];
	}

	/**
	 * Fix for non atomic queries (MyISAM  etc) and saveAll to still return just the boolean result
	 * Otherwise you would have to iterate over all result values to find out if the save was successful.
	 *
	 * Use Configure::read('Model.atomic') to modify atomic behavior.
	 * Additional options:
	 *
	 * - returnArray: bool
	 *
	 * @param mixed $data
	 * @param array $options
	 * @return boolean Success
	 */
	public function saveAll($data = null, $options = array()) {
		if (!isset($options['atomic']) && Configure::read('Model.atomic') !== null) {
			$options['atomic'] = (bool)Configure::read('Model.atomic');
		}
		$res = parent::saveAll($data, $options);

		if (is_array($res) && empty($options['returnArray'])) {
			$res = Utility::isValidSaveAll($res);
		}
		return $res;
	}

	/**
	 * Enables HABTM-Validation
	 * e.g. with
	 * 'rule' => array('multiple', array('min' => 2))
	 *
	 * @return boolean Success
	 */
	public function beforeValidate($options = array()) {
		foreach ($this->hasAndBelongsToMany as $k => $v) {
			if (isset($this->data[$k][$k])) {
				$this->data[$this->alias][$k] = $this->data[$k][$k];
			}
		}

		return parent::beforeValidate($options);
	}

	/**
	 * @param params
	 * - key: functioName or other key used
	 * @return boolean Success
	 */
	public function deleteCache($key = null) {
		$key = Inflector::underscore($key);
		if (!empty($key)) {
			return Cache::delete(strtolower(Inflector::underscore($this->alias)) . '__' . $key, 'sql');
		}
		return Cache::clear(false, 'sql');
	}

	/**
	 * Generates a SQL subquery snippet to be used in your actual query.
	 * Your subquery snippet needs to return a single value or flat array of values.
	 *
	 * Example:
	 *
	 *   $this->Model->find('first', array(
	 *     'conditions' => array('NOT' => array('some_id' => $this->Model->subquery(...)))
	 *   ))
	 *
	 * Note: You might have to set `autoFields` to false in order to retrieve only the fields you request:
	 * http://book.cakephp.org/2.0/en/core-libraries/behaviors/containable.html#containablebehavior-options
	 *
	 * @param string $type The type of the query ('count'/'all'/'first' - first only works with some mysql versions)
	 * @param array $options The options array
	 * @param string $alias You can use this intead of $options['alias'] if you want
	 * @param boolean $parenthesise Add parenthesis before and after
	 * @return string result sql snippet of the query to run
	 * @modified Mark Scherer (cake2.x ready and improvements)
	 * @link http://bakery.cakephp.org/articles/lucaswxp/2011/02/11/easy_and_simple_subquery_cakephp
	 */
	public function subquery($type, $options = array(), $alias = null, $parenthesise = true) {
		if ($alias === null) {
			$alias = 'Sub' . $this->alias . '';
		}

		$fields = array($alias . '.id');
		$limit = null;
		switch ($type) {
			case 'count':
				$fields = array('COUNT(*)');
				break;
			case 'first':
				$limit = 1;
				break;
		}

		$dbo = $this->getDataSource();

		$default = array(
			'fields' => $fields,
			'table' => $dbo->fullTableName($this),
			'alias' => $alias,
			'limit' => $limit,
			'offset' => null,
			'joins' => array(),
			'conditions' => array(),
			'order' => null,
			'group' => null
		);
		$params = array_merge($default, $options);
		$subQuery = trim($dbo->buildStatement($params, $this));
		if ($parenthesise) {
			$subQuery = '(' . $subQuery . ')';
		}
		return $subQuery;
	}

	/**
	 * Wrapper find() to cache sql queries.
	 *
	 * @param array $conditions
	 * @param array $fields
	 * @param string $order
	 * @param string $recursive
	 * @return array
	 */
	public function find($type = null, $query = array()) {
		// reset/delete
		if (!empty($query['reset'])) {
			if (!empty($query['cache'])) {
				if (is_array($query['cache'])) {
					$key = $query['cache'][0];
				} else {
					$key = $query['cache'];
					if ($key === true) {
						$backtrace = debug_backtrace();
						$key = $backtrace[1]['function'];
					}
				}
				$this->deleteCache($key);
			}
		}

		// custom fixes
		if (is_string($type)) {
			switch ($type) {
				case 'count':
					if (isset($query['fields'])) {
						unset($query['fields']);
					}
					break;
				default:
			}
		}

		// having and group clauses enhancement
		if (is_array($query) && !empty($query['having']) && !empty($query['group'])) {
			if (!is_array($query['group'])) {
				$query['group'] = array($query['group']);
			}
			$ds = $this->getDataSource();
			$having = $ds->conditions($query['having'], true, false);
			$query['group'][count($query['group']) - 1] .= " HAVING $having";
		} /* elseif (is_array($query) && !empty($query['having'])) {
			$ds = $this->getDataSource();
			$having = $ds->conditions($query['having'], true, false);
			$query['conditions'][] = '1=1 HAVING '.$having;
		}
		*/

		// find
		if (!Configure::read('Cache.disable') && Configure::read('Cache.check') && !empty($query['cache'])) {
			if (is_array($query['cache'])) {
				$key = $query['cache'][0];
				$expires = DAY;
				if (!empty($query['cache'][1])) {
					$expires = $query['cache'][1];
				}
			} else {
				$key = $query['cache'];
				if ($key === true) {
					$backtrace = debug_backtrace();
					$key = $backtrace[1]['function'];
				}
				$expires = DAY;
			}

			$options = array('prefix' => strtolower(Inflector::underscore($this->alias)) . '__', );
			if (!empty($expires)) {
				$options['duration'] = $expires;
			}
			if (!Configure::read('Cache.disable')) {
				Cache::config('sql', $options);

				$key = Inflector::underscore($key);
				$results = Cache::read($key, 'sql');
			}

			if ($results === null) {
				$results = parent::find($type, $query);
				Cache::write($key, $results, 'sql');
			}
			return $results;
		}

		// Without caching
		return parent::find($type, $query);
	}

	/**
	 * This code will add formatted list functionallity to find you can easy replace the $this->Model->find('list'); with $this->Model->find('formattedlist', array('fields' => array('Model.id', 'Model.field1', 'Model.field2', 'Model.field3'), 'format' => '%s-%s %s')); and get option tag output of: Model.field1-Model.field2 Model.field3. Even better part is being able to setup your own format for the output!
	 *
	 * @see http://bakery.cakephp.org/articles/view/add-formatted-lists-to-your-appmodel
	 * @deprecated
	 * added Caching
	 */
	protected function _find($type, $options = array()) {
		$res = false;
		if ($res === false) {
			if (isset($options['cache'])) {
				unset($options['cache']);
			}
			if (!isset($options['recursive'])) {
				//$options['recursive'] = -1;
			}

			switch ($type) {
					// @see http://bakery.cakephp.org/deu/articles/nate/2010/10/10/quick-tipp_-_doing_ad-hoc-joins_bei_model_find
				case 'matches':
					if (!isset($options['joins'])) {
						$options['joins'] = array();
					}

					if (!isset($options['model']) || !isset($options['scope'])) {
						break;
					}
					$assoc = $this->hasAndBelongsToMany[$options['model']];
					$bind = "{$assoc['with']}.{$assoc['foreignKey']} = {$this->alias}.{$this->primaryKey}";

					$options['joins'][] = array(
						'table' => $assoc['joinTable'],
						'alias' => $assoc['with'],
						'type' => 'inner',
						'foreignKey' => false,
						'conditions' => array($bind)
					);

					$bind = $options['model'] . '.' . $this->{$options['model']}->primaryKey . ' = ';
					$bind .= "{$assoc['with']}.{$assoc['associationForeignKey']}";

					$options['joins'][] = array(
						'table' => $this->{$options['model']}->table,
						'alias' => $options['model'],
						'type' => 'inner',
						'foreignKey' => false,
						'conditions' => array($bind) + (array)$options['scope'],
					);
					unset($options['model'], $options['scope']);
					$type = 'all';
					break;
					// probably deprecated since "virtual fields" in 1.3
				case 'formattedlist':
					if (!isset($options['fields']) || count($options['fields']) < 3) {
						$res = parent::find('list', $options);
						break;
					}

					$this->recursive = -1;
					//setup formating
					$format = '';
					if (!isset($options['format'])) {
						for ($i = 0; $i < (count($options['fields']) - 1); $i++) $format .= '%s ';

						$format = substr($format, 0, -1);
					} else {
						$format = $options['format'];
					}
					//get data
					$list = parent::find('all', $options);
					// remove model alias from strings to only get field names
					$tmpPath2[] = $format;
					for ($i = 1; $i <= (count($options['fields']) - 1); $i++) {
						$field[$i] = str_replace($this->alias . '.', '', $options['fields'][$i]);
						$tmpPath2[] = '{n}.' . $this->alias . '.' . $field[$i];
					}
					//do the magic?? read the code...
					$res = Set::combine($list, '{n}.' . $this->alias . '.' . $this->primaryKey, $tmpPath2);
					break;
				default:
					$res = parent::find($type, $options);
			}
			if (!empty($this->useCache)) {
				Cache::write($this->cacheName, $res, $this->cacheConfig);
				if (Configure::read('debug') > 0) {
					$this->log('WRITE (' . $this->cacheConfig . '): ' . $this->cacheName, 'cache');
				}
			}
		} else {
			if (Configure::read('debug') > 0) {
				$this->log('READ (' . $this->cacheConfig . '): ' . $this->cacheName, 'cache');
			}
		}
		return $res;
	}

	/**
	 * Core-fix for multiple sort orders
	 *
	 * @param addiotional 'scope'=>array(field,order) - value is retrieved by (submitted) primary key
	 * @return mixed
	 * TODO: fix it
	 */
	protected function _findNeighbors($state, $query, $results = array()) {
		return parent::_findNeighbors($state, $query, $results);

		if (isset($query['scope'])) {
			//TODO
		}
		return parent::find($type, $options);
	}

	/**
	 * @param mixed $id: id only, or request array
	 * @param array $options
	 * - filter: open/closed/none
	 * - field (sortField, if not id)
	 * - reverse: sortDirection (0=normalAsc/1=reverseDesc)
	 * - displayField: ($this->displayField, if empty)
	 * @param array $qryOptions
	 * - recursive (defaults to -1)
	 * TODO: try to use core function, TRY TO ALLOW MULTIPLE SORT FIELDS
	 * @return array
	 */
	public function neighbors($id = null, $options = array(), $qryOptions = array()) {
		$sortField = (!empty($options['field']) ? $options['field'] : 'created');
		$normalDirection = (!empty($options['reverse']) ? false : true);
		$sortDirWord = $normalDirection ? array('ASC', 'DESC') : array('DESC', 'ASC');
		$sortDirSymb = $normalDirection ? array('>=', '<=') : array('<=', '>=');

		$displayField = (!empty($options['displayField']) ? $options['displayField'] : $this->displayField);

		if (is_array($id)) {
			$data = $id;
			$id = $data[$this->alias][$this->primaryKey];
		} elseif ($id === null) {
			$id = $this->id;
		}
		if (!empty($id)) {
			$data = $this->find('first', array('conditions' => array($this->primaryKey => $id), 'contain' => array()));
		}

		if (empty($id) || empty($data) || empty($data[$this->alias][$sortField])) {
			return array();
		} else {
			$field = $data[$this->alias][$sortField];
		}
		$findOptions = array('recursive' => -1);
		if (isset($qryOptions['recursive'])) {
			$findOptions['recursive'] = $qryOptions['recursive'];
		}
		if (isset($qryOptions['contain'])) {
			$findOptions['contain'] = $qryOptions['contain'];
		}

		$findOptions['fields'] = array($this->alias . '.' . $this->primaryKey, $this->alias . '.' . $displayField);
		$findOptions['conditions'][$this->alias . '.' . $this->primaryKey . ' !='] = $id;

		// //TODO: take out
		if (!empty($options['filter']) && $options['filter'] == REQUEST_STATUS_FILTER_OPEN) {
			$findOptions['conditions'][$this->alias . '.status <'] = REQUEST_STATUS_DECLINED;
		} elseif (!empty($options['filter']) && $options['filter'] == REQUEST_STATUS_FILTER_CLOSED) {
			$findOptions['conditions'][$this->alias . '.status >='] = REQUEST_STATUS_DECLINED;
		}

		$return = array();

		if (!empty($qryOptions['conditions'])) {
			$findOptions['conditions'] = Set::merge($findOptions['conditions'], $qryOptions['conditions']);
		}

		$options = $findOptions;
		$options['conditions'] = Set::merge($options['conditions'], array($this->alias . '.' . $sortField . ' ' . $sortDirSymb[1] => $field));
		$options['order'] = array($this->alias . '.' . $sortField . '' => $sortDirWord[1]);
		$this->id = $id;
		$return['prev'] = $this->find('first', $options);

		$options = $findOptions;
		$options['conditions'] = Set::merge($options['conditions'], array($this->alias . '.' . $sortField . ' ' . $sortDirSymb[0] => $field));
		$options['order'] = array($this->alias . '.' . $sortField . '' => $sortDirWord[0]); // ??? why 0 instead of 1
		$this->id = $id;
		$return['next'] = $this->find('first', $options);

		return $return;
	}

	/**
	 * Delete all records using an atomic query similar to updateAll().
	 * Note: Does not need manual sanitizing/escaping, though.
	 *
	 * Does not do any callbacks
	 *
	 * @param mixed $conditions Conditions to match, true for all records
	 * @return boolean Success
	 */
	public function deleteAllRaw($conditions = true) {
		return $this->getDataSource()->delete($this, $conditions);
	}

	/**
	 * Overwrite invalidate to allow last => true
	 *
	 * @param string $field The name of the field to invalidate
	 * @param mixed $value Name of validation rule that was not failed, or validation message to
	 *    be returned. If no validation key is provided, defaults to true.
	 * @param boolean $last If this should be the last validation check for this validation run
	 * @return void
	 */
	public function invalidate($field, $value = true, $last = false) {
		parent::invalidate($field, $value);
		if (!$last) {
			return;
		}

		$this->validator()->remove($field);
	}

	/**
	 * Validates a primary or foreign key depending on the current schema data for this field
	 * recognizes uuid (char36) and aiid (int10 unsigned) - not yet mixed (varchar36)
	 * more useful than using numeric or notEmpty which are type specific
	 *
	 * @param array $data
	 * @param array $options
	 * - allowEmpty
	 * @return boolean Success
	 */
	public function validateKey($data = array(), $options = array()) {
		$keys = array_keys($data);
		$key = array_shift($keys);
		$value = array_shift($data);

		$schema = $this->schema($key);
		if (!$schema) {
			return true;
		}

		$defaults = array(
			'allowEmpty' => false,
		);
		$options = array_merge($defaults, $options);

		if ($schema['type'] !== 'integer') {
			if ($options['allowEmpty'] && $value === '') {
				return true;
			}
			return Validation::uuid($value);
		}
		if ($options['allowEmpty'] && $value === 0) {
			return true;
		}
		return is_numeric($value) && (int)$value == $value && $value > 0;
	}

	/**
	 * Checks if the passed enum value is valid
	 *
	 * @return boolean Success
	 */
	public function validateEnum(array $data, $enum = null, $additionalKeys = array()) {
		$keys = array_keys($data);
		$valueKey = array_shift($keys);
		$value = $data[$valueKey];
		$keys = array();
		if ($enum === true) {
			$enum = $valueKey;
		}
		if ($enum !== null) {
			if (!method_exists($this, $enum)) {
				trigger_error('Enum method \'' . $enum . '()\' not exists', E_USER_ERROR);
				return false;
			}
			//TODO: make static
			$keys = $this->{$enum}();
		}
		$keys = array_merge($additionalKeys, array_keys($keys));
		if (!empty($keys) && in_array($value, $keys)) {
			return true;
		}
		return false;
	}

	/**
	 * Checks if the content of 2 fields are equal
	 * Does not check on empty fields! Return TRUE even if both are empty (secure against empty in another rule)!
	 *
	 * @return boolean Success
	 */
	public function validateIdentical($data = array(), $compareWith = null, $options = array()) {
		if (is_array($data)) {
			$value = array_shift($data);
		} else {
			$value = $data;
		}
		$compareValue = $this->data[$this->alias][$compareWith];

		$matching = array('string' => 'string', 'int' => 'integer', 'float' => 'float', 'bool' => 'boolean');
		if (!empty($options['cast']) && array_key_exists($options['cast'], $matching)) {
			// cast values to string/int/float/bool if desired
			settype($compareValue, $matching[$options['cast']]);
			settype($value, $matching[$options['cast']]);
		}
		return ($compareValue === $value);
	}

	/**
	 * Checks a record, if it is unique - depending on other fields in this table (transfered as array)
	 * example in model: 'rule' => array ('validateUnique', array('belongs_to_table_id','some_id','user_id')),
	 * if all keys (of the array transferred) match a record, return false, otherwise true
	 *
	 * @param array $fields Other fields to depend on
	 * TODO: add possibity of deep nested validation (User -> Comment -> CommentCategory: UNIQUE comment_id, Comment.user_id)
	 * @param array $options
	 * - requireDependentFields Require all dependent fields for the validation rule to return true
	 * @return boolean Success
	 */
	public function validateUnique($data, $fields = array(), $options = array()) {
		$id = (!empty($this->data[$this->alias][$this->primaryKey]) ? $this->data[$this->alias][$this->primaryKey] : 0);
		if (!$id && $this->id) {
			$id = $this->id;
		}

		foreach ($data as $key => $value) {
			$fieldName = $key;
			$fieldValue = $value;
			break;
		}

		$conditions = array(
			$this->alias . '.' . $fieldName => $fieldValue,
			$this->alias . '.id !=' => $id);

		// careful, if fields is not manually filled, the options will be the second param!!! big problem...
		$fields = (array)$fields;
		if (!array_key_exists('allowEmpty', $fields)) {
			foreach ($fields as $dependingField) {
				if (isset($this->data[$this->alias][$dependingField])) { // add ONLY if some content is transfered (check on that first!)
					$conditions[$this->alias . '.' . $dependingField] = $this->data[$this->alias][$dependingField];

				} elseif (isset($this->data['Validation'][$dependingField])) { // add ONLY if some content is transfered (check on that first!
					$conditions[$this->alias . '.' . $dependingField] = $this->data['Validation'][$dependingField];

				} elseif (!empty($id)) {
					// manual query! (only possible on edit)
					$res = $this->find('first', array('fields' => array($this->alias . '.' . $dependingField), 'conditions' => array($this->alias . '.id' => $id)));
					if (!empty($res)) {
						$conditions[$this->alias . '.' . $dependingField] = $res[$this->alias][$dependingField];
					}
				} else {
					if (!empty($options['requireDependentFields'])) {
						trigger_error('Required field ' . $dependingField . ' for validateUnique validation not present');
						return false;
					}
					return true;
				}
			}
		}

		$this->recursive = -1;
		if (count($conditions) > 2) {
			$this->recursive = 0;
		}
		$options = array('fields' => array($this->alias . '.' . $this->primaryKey), 'conditions' => $conditions);
		$res = $this->find('first', $options);
		return empty($res);
	}

	/**
	 * Alterative for validating unique fields.
	 *
	 * @param array $data
	 * @param array $options
	 * - scope (array of other fields as scope - isUnique dependent on other fields of the table)
	 * - batch (defaults to true, remembers previous values in order to validate batch imports)
	 * example in model: 'rule' => array ('validateUniqueExt', array('scope'=>array('belongs_to_table_id','some_id','user_id'))),
	 * http://groups.google.com/group/cake-php/browse_thread/thread/880ee963456739ec
	 * //TODO: test!!!
	 * @return boolean Success
	 * @deprecated in favor of validateUnique?
	 */
	public function validateUniqueExt($data, $options = array()) {
		foreach ($data as $key => $value) {
			$fieldName = $key;
			$fieldValue = $value;
		}
		$defaults = array('batch' => true, 'scope' => array());
		$options = array_merge($defaults, $options);

		// for batch
		if ($options['batch'] !== false && !empty($this->batchRecords)) {
			if (array_key_exists($value, $this->batchRecords[$fieldName])) {
				return $options['scope'] === $this->batchRecords[$fieldName][$value];
			}
		}

		// continue with validation
		if (!$this->validateUnique($data, $options['scope'])) {
			return false;
		}

		// for batch
		if ($options['batch'] !== false) {
			if (!isset($this->batchRecords)) {
				$this->batchRecords = array();
			}
			$this->batchRecords[$fieldName][$value] = $scope;
		}
		return true;
	}

	/**
	 * Checks if a url is valid AND accessable (returns false otherwise)
	 *
	 * @param array/string $data: full url(!) starting with http://...
	 * @options array
	 * - allowEmpty TRUE/FALSE (TRUE: if empty => return TRUE)
	 * - required TRUE/FALSE (TRUE: overrides allowEmpty)
	 * - autoComplete (default: TRUE)
	 * - deep (default: TRUE)
	 * @return boolean Success
	 */
	public function validateUrl($data, $options = array()) {
		if (is_array($data)) {
			foreach ($data as $key => $url) {
				break;
			}
		} else {
			$url = $data;
		}

		if (empty($url)) {
			if (!empty($options['allowEmpty']) && empty($options['required'])) {
				return true;
			}
			return false;
		}
		if (!isset($options['autoComplete']) || $options['autoComplete'] !== false) {
			$url = $this->_autoCompleteUrl($url);
			if (isset($key)) {
				$this->data[$this->alias][$key] = $url;
			}
		}

		if (!isset($options['strict']) || $options['strict'] !== false) {
			$options['strict'] = true;
		}

		// validation
		if (!Validation::url($url, $options['strict']) && env('REMOTE_ADDR') && env('REMOTE_ADDR') !== '127.0.0.1') {
			return false;
		}
		// same domain?
		if (!empty($options['sameDomain']) && env('HTTP_HOST')) {
			$is = parse_url($url, PHP_URL_HOST);
			$expected = env('HTTP_HOST');
			if (mb_strtolower($is) !== mb_strtolower($expected)) {
				return false;
			}
		}

		if (isset($options['deep']) && $options['deep'] === false) {
			return true;
		}
		return $this->_validUrl($url);
	}

	/**
	 * Prepend protocol if missing
	 *
	 * @param string $url
	 * @return string Url
	 */
	protected function _autoCompleteUrl($url) {
		if (mb_strpos($url, '/') === 0) {
			$url = Router::url($url, true);
		} elseif (mb_strpos($url, '://') === false && mb_strpos($url, 'www.') === 0) {
			$url = 'http://' . $url;
		}
		return $url;
	}

	/**
	 * Checks if a url is valid
	 *
	 * @param string url
	 * @return boolean Success
	 */
	protected function _validUrl($url) {
		$headers = Utility::getHeaderFromUrl($url);
		if ($headers === false) {
			return false;
		}
		$headers = implode("\n", $headers);
		$protocol = mb_strpos($url, 'https://') === 0 ? 'HTTP' : 'HTTP';
		if (!preg_match('#^' . $protocol . '/.*?\s+[(200|301|302)]+\s#i', $headers)) {
			return false;
		}
		if (preg_match('#^' . $protocol . '/.*?\s+[(404|999)]+\s#i', $headers)) {
			return false;
		}
		return true;
	}

	/**
	 * Validation of DateTime Fields (both Date and Time together)
	 *
	 * @param options
	 * - dateFormat (defaults to 'ymd')
	 * - allowEmpty
	 * - after/before (fieldName to validate against)
	 * - min/max (defaults to >= 1 - at least 1 minute apart)
	 * @return boolean Success
	 */
	public function validateDateTime($data, $options = array()) {
		$format = !empty($options['dateFormat']) ? $options['dateFormat'] : 'ymd';

		if (is_array($data)) {
			$value = array_shift($data);
		} else {
			$value = $data;
		}

		$dateTime = explode(' ', trim($value), 2);
		$date = $dateTime[0];
		$time = (!empty($dateTime[1]) ? $dateTime[1] : '');

		if (!empty($options['allowEmpty']) && (empty($date) && empty($time) || $date == DEFAULT_DATE && $time == DEFAULT_TIME || $date == DEFAULT_DATE && empty($time))) {
			return true;
		}
		/*
		if ($this->validateDate($date, $options) && $this->validateTime($time, $options)) {
			return true;
		}
		*/
		if (Validation::date($date, $format) && Validation::time($time)) {
			// after/before?
			$minutes = isset($options['min']) ? $options['min'] : 1;
			if (!empty($options['after']) && isset($this->data[$this->alias][$options['after']])) {
				if (strtotime($this->data[$this->alias][$options['after']]) > strtotime($value) - $minutes) {
					return false;
				}
			}
			if (!empty($options['before']) && isset($this->data[$this->alias][$options['before']])) {
				if (strtotime($this->data[$this->alias][$options['before']]) < strtotime($value) + $minutes) {
					return false;
				}
			}
			return true;
		}
		return false;
	}

	/**
	 * Validation of Date fields (as the core one is buggy!!!)
	 *
	 * @param options
	 * - dateFormat (defaults to 'ymd')
	 * - allowEmpty
	 * - after/before (fieldName to validate against)
	 * - min (defaults to 0 - equal is OK too)
	 * @return boolean Success
	 */
	public function validateDate($data, $options = array()) {
		$format = !empty($options['format']) ? $options['format'] : 'ymd';
		if (is_array($data)) {
			$value = array_shift($data);
		} else {
			$value = $data;
		}

		$dateTime = explode(' ', trim($value), 2);
		$date = $dateTime[0];

		if (!empty($options['allowEmpty']) && (empty($date) || $date == DEFAULT_DATE)) {
			return true;
		}
		if (Validation::date($date, $format)) {
			// after/before?
			$days = !empty($options['min']) ? $options['min'] : 0;
			if (!empty($options['after']) && isset($this->data[$this->alias][$options['after']])) {
				if ($this->data[$this->alias][$options['after']] > date(FORMAT_DB_DATE, strtotime($date) - $days * DAY)) {
					return false;
				}
			}
			if (!empty($options['before']) && isset($this->data[$this->alias][$options['before']])) {
				if ($this->data[$this->alias][$options['before']] < date(FORMAT_DB_DATE, strtotime($date) + $days * DAY)) {
					return false;
				}
			}
			return true;
		}
		return false;
	}

	/**
	 * Validation of Time fields
	 *
	 * @param array $options
	 * - timeFormat (defaults to 'hms')
	 * - allowEmpty
	 * - after/before (fieldName to validate against)
	 * - min/max (defaults to >= 1 - at least 1 minute apart)
	 * @return boolean Success
	 */
	public function validateTime($data, $options = array()) {
		if (is_array($data)) {
			$value = array_shift($data);
		} else {
			$value = $data;
		}

		$dateTime = explode(' ', trim($value), 2);
		$value = array_pop($dateTime);

		if (Validation::time($value)) {
			// after/before?
			if (!empty($options['after']) && isset($this->data[$this->alias][$options['after']])) {
				if ($this->data[$this->alias][$options['after']] >= $value) {
					return false;
				}
			}
			if (!empty($options['before']) && isset($this->data[$this->alias][$options['before']])) {
				if ($this->data[$this->alias][$options['before']] <= $value) {
					return false;
				}
			}
			return true;
		}
		return false;
	}

	/**
	 * Validation of Date Fields (>= minDate && <= maxDate)
	 *
	 * @param options
	 * - min/max (TODO!!)
	 */
	public function validateDateRange($data, $options = array()) {
	}

	/**
	 * Validation of Time Fields (>= minTime && <= maxTime)
	 *
	 * @param options
	 * - min/max (TODO!!)
	 */
	public function validateTimeRange($data, $options = array()) {
	}

	/**
	 * Model validation rule for email addresses
	 *
	 * @return boolean Success
	 */
	public function validateUndisposable($data, $proceed = false) {
		$email = array_shift($data);
		if (empty($email)) {
			return true;
		}
		return $this->isUndisposableEmail($email, false, $proceed);
	}

	/**
	 * NOW: can be set to work offline only (if server is down etc)
	 * Checks if a email is not from a garbage hoster
	 *
	 * @param string email (necessary)
	 * @return boolean true if valid, else false
	 */
	public function isUndisposableEmail($email, $onlineMode = false, $proceed = false) {
		if (!isset($this->UndisposableEmail)) {
			App::import('Vendor', 'undisposable/undisposable');
			$this->UndisposableEmail = new UndisposableEmail();
		}
		if (!$onlineMode) {
			// crashed with white screen of death otherwise... (if foreign page is 404)
			$this->UndisposableEmail->useOnlineList(false);
		}
		if (!class_exists('Validation')) {
			App::uses('Validation', 'Utility');
		}
		if (!Validation::email($email)) {
			return false;
		}
		if ($this->UndisposableEmail->isUndisposableEmail($email) === false) {
			// trigger log
			$this->log('Disposable Email detected: ' . h($email) . ' (IP ' . env('REMOTE_ADDR') . ')', 'undisposable');
			if ($proceed === true) {
				return true;
			}
			return false;
		}
		return true;
	}

	/**
	 * Is blocked email?
	 * //TODO: move outside of MyModel?
	 *
	 * @return boolean ifNotBlacklisted
	 */
	public function validateNotBlocked($params) {
		$email = array_shift($params);
		if (!isset($this->Blacklist)) {
			$this->Blacklist = ClassRegistry::init('Tools.Blacklist');
		}
		if ($this->Blacklist->isBlacklisted(Blacklist::TYPE_EMAIL, $email)) {
			return false;
		}
		return true;
	}

/** General Model Functions **/

	/**
	 * CAREFUL: use LIMIT due to Starker Serverlastigkeit! or CACHE it!
	 *
	 * e.g.: 'ORDER BY ".$this->umlautsOrderFix('User.nic')." ASC'
	 *
	 * @param string variable (to be correctly ordered)
	 * @deprecated
	 */
	public function umlautsOrderFix($var) {
		return "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(" . $var . ", 'Ä', 'Ae'), 'Ö', 'Oe'), 'Ü', 'Ue'), 'ä', 'ae'), 'ö', 'oe'), 'ü','ue'), 'ß', 'ss')";
	}

	/**
	 * Set + guaranteeFields!
	 * Extends the core set function (only using data!!!)
	 *
	 * @param mixed $data
	 * @param mixed $data2 (optional)
	 * @param array $requiredFields Required fields
	 * @param array $fieldList Whitelist / Allowed fields
	 * @return array
	 */
	public function set($data, $data2 = null, $requiredFields = array(), $fieldList = array()) {
		if (!empty($requiredFields)) {
			$data = $this->guaranteeFields($requiredFields, $data);
		}
		if (!empty($fieldList)) {
			$data = $this->whitelist($fieldList, $data);
		}
		return parent::set($data, $data2);
	}

	/**
	 * @param array $fieldList
	 * @param array $data (optional)
	 * @return array
	 */
	public function whitelist(array $fieldList, $data = null) {
		$model = $this->alias;
		if ($data === null) {
			$data =& $this->data;
		}
		if (empty($data[$model])) {
			return array();
		}
		foreach ($data[$model] as $key => $val) {
			if (!in_array($key, $fieldList)) {
				unset($data[$model][$key]);
			}
		}
		return $data;
	}

	/**
	 * Instead of whitelisting this will remove all blacklisted keys.
	 *
	 * @param array $blacklist
	 * - array: fields to blacklist
	 * - boolean TRUE: removes all foreign_keys (_id)
	 * note: one-dimensional
	 * @return array
	 */
	public function blacklist($blacklist, $data = null) {
		$model = $this->alias;
		if ($data === null) {
			$data =& $this->data;
		}
		if (empty($data[$model])) {
			return array();
		}
		if ($blacklist === true) {
			foreach ($data[$model] as $key => $value) {
				if (substr($key, -3, 3) === '_id') {
					unset($data[$model][$key]);
				}
			}
			return;
		}
		foreach ($blacklist as $key) {
			if (isset($data[$model][$key])) {
				unset($data[$model][$key]);
			}
		}
		return $data;
	}

	/**
	 * Generate a whitelist, based on the current schema and a passed blacklist.
	 *
	 * @param array $blacklist
	 * @return array
	 */
	public function generateWhitelistFromBlacklist(array $blacklist) {
		return array_diff(array_keys($this->schema()), $blacklist);
	}

	/**
	 * Make sure required fields exists - in order to properly validate them
	 *
	 * @param array: field1, field2 - or field1, Model2.field1 etc
	 * @param array: data (optional, otherwise the array with the required fields will be returned)
	 * @return array
	 */
	public function guaranteeFields($requiredFields, $data = null) {
		$guaranteedFields = array();
		foreach ($requiredFields as $column) {
			if (strpos($column, '.') !== false) {
				list($model, $column) = explode('.', $column, 2);
			} else {
				$model = $this->alias;
			}
			$guaranteedFields[$model][$column] = ''; # now field exists in any case!
		}
		if ($data === null) {
			return $guaranteedFields;
		}
		if (!empty($guaranteedFields)) {
			$data = Set::merge($guaranteedFields, $data);
		}
		return $data;
	}

	/**
	 * Make certain fields a requirement for the form to validate
	 * (they must only be present - can still be empty, though!)
	 *
	 * @param array $fieldList
	 * @param boolean $allowEmpty (or NULL to not touch already set elements)
	 * @return void
	 */
	public function requireFields($requiredFields, $allowEmpty = null) {
		if ($allowEmpty === null) {
			$setAllowEmpty = true;
		} else {
			$setAllowEmpty = $allowEmpty;
		}

		foreach ($requiredFields as $column) {
			if (strpos($column, '.') !== false) {
				list($model, $column) = explode('.', $column, 2);
			} else {
				$model = $this->alias;
			}

			if ($model !== $this->alias) {
				continue;
			}

			if (empty($this->validate[$column])) {
				$this->validate[$column]['notEmpty'] = array('rule' => 'notEmpty', 'required' => true, 'allowEmpty' => $setAllowEmpty, 'message' => 'valErrMandatoryField');
			} else {
				$keys = array_keys($this->validate[$column]);
				if (!in_array('rule', $keys)) {
					$key = array_shift($keys);
					$this->validate[$column][$key]['required'] = true;
					if (!isset($this->validate[$column][$key]['allowEmpty'])) {
						$this->validate[$column][$key]['allowEmpty'] = $setAllowEmpty;
					}
				} else {
					$keys['required'] = true;
					if (!isset($keys['allowEmpty'])) {
						$keys['allowEmpty'] = $setAllowEmpty;
					}
					$this->validate[$column] = $keys;
				}
			}
		}
	}

	/**
	 * Shortcut method to find a specific entry via primary key.
	 *
	 * Either provide the id directly:
	 *
	 *   $record = $this->Model->get($id);
	 *
	 * Or use
	 *
	 *   $this->Model->id = $id;
	 *   $record = $this->Model->get();
	 *
	 * @param mixed $id
	 * @param string|array $fields
	 * @param array $contain
	 * @return mixed
	 */
	public function get($id = null, $fields = array(), $contain = array()) {
		if (is_array($id)) {
			$column = $id[0];
			$value = $id[1];
		} else {
			$column = $this->primaryKey;
			$value = $id;
			if ($value === null) {
				$value = $this->id;
			}
		}
		if (!$value) {
			return array();
		}

		if ($fields === '*') {
			$fields = $this->alias . '.*';
		} elseif (!empty($fields)) {
			foreach ($fields as $row => $field) {
				if (strpos($field, '.') !== false) {
					continue;
				}
				$fields[$row] = $this->alias . '.' . $field;
			}
		}

		$options = array(
			'conditions' => array($this->alias . '.' . $column => $value),
		);
		if (!empty($fields)) {
			$options['fields'] = $fields;
		}
		if (!empty($contain)) {
			$options['contain'] = $contain;
		}
		return $this->find('first', $options);
	}

	/**
	 * Get all related entries that have been used so far
	 *
	 * @param string $modelName The related model
	 * @param string $groupField Field to group by
	 * @param string $type Find type
	 * @param array $options
	 * @return array
	 */
	public function getRelatedInUse($modelName, $groupField = null, $type = 'all', $options = array()) {
		if ($groupField === null) {
			$groupField = $this->belongsTo[$modelName]['foreignKey'];
		}
		$defaults = array(
			'contain' => array($modelName),
			'group' => $groupField,
			'order' => $this->$modelName->order ? $this->$modelName->order : array($modelName . '.' . $this->$modelName->displayField => 'ASC'),
		);
		if ($type === 'list') {
			$defaults['fields'] = array($modelName . '.' . $this->$modelName->primaryKey, $modelName . '.' . $this->$modelName->displayField);
		}
		$options += $defaults;
		return $this->find($type, $options);
	}

	/**
	 * Get all fields that have been used so far
	 *
	 * @param string $groupField Field to group by
	 * @param string $type Find type
	 * @param array $options
	 * @return array
	 */
	public function getFieldInUse($groupField, $type = 'all', $options = array()) {
		$defaults = array(
			'group' => $groupField,
			'order' => array($this->alias . '.' . $this->displayField => 'ASC'),
		);
		if ($type === 'list') {
			$defaults['fields'] = array($this->alias . '.' . $this->primaryKey, $this->alias . '.' . $this->displayField);
		}
		$options += $defaults;
		return $this->find($type, $options);
	}

	/**
	 * Update a row with certain fields (dont use "Model" as super-key)
	 *
	 * @param integer $id
	 * @param array $data
	 * @return boolean|array Success
	 */
	public function update($id, $data, $validate = false) {
		$this->id = $id;
		return $this->save($data, $validate, array_keys($data));
	}

	/**
	 * Automagic increasing of a field with e.g.:
	 * $this->id = ID; $this->inc('weight',3);
	 *
	 * @deprecated use atomic updateAll() instead!
	 * @param string fieldname
	 * @param integer factor: defaults to 1 (could be negative as well - if field is signed and can be < 0)
	 */
	public function inc($field, $factor = 1) {
		$value = Set::extract($this->read($field), $this->alias . '.' . $field);
		$value += $factor;
		return $this->saveField($field, $value);
	}

	/**
	 * Toggles Field (Important/Deleted/Primary etc)
	 *
	 * @param STRING fieldName
	 * @param integer id (cleaned!)
	 * @return ARRAY record: [Model][values],...
	 * AJAX?
	 */
	public function toggleField($fieldName, $id) {
		$record = $this->get($id, array($this->primaryKey, $fieldName));

		if (!empty($record) && !empty($fieldName) && $this->hasField($fieldName)) {
			$record[$this->alias][$fieldName] = ($record[$this->alias][$fieldName] == 1 ? 0 : 1);
			$this->id = $id;
			$this->saveField($fieldName, $record[$this->alias][$fieldName]);
		}
		return $record;
	}

	/**
	 * Truncate TABLE (already validated, that table exists)
	 *
	 * @param string table [default:FALSE = current model table]
	 * @return boolean Success
	 */
	public function truncate($table = null) {
		if (empty($table)) {
			$table = $this->table;
		}
		$db = ConnectionManager::getDataSource($this->useDbConfig);
		return $db->truncate($table);
	}

/** Deep Lists **/

	/**
	 * Recursive Dropdown Lists
	 * NEEDS tree behavior, NEEDS lft, rght, parent_id (!)
	 * //FIXME
	 */
	public function recursiveSelect($conditions = array(), $attachTree = false, $spacer = '-- ') {
		if ($attachTree) {
			$this->Behaviors->load('Tree');
		}
		$data = $this->generateTreeList($conditions, null, null, $spacer);
		return $data;
	}

	/**
	 * From http://othy.wordpress.com/2006/06/03/generatenestedlist/
	 * NEEDS parent_id
	 * //TODO refactor for 1.2
	 *
	 * @deprecated use generateTreeList instead
	 */
	public function generateNestedList($conditions = null, $indent = '--') {
		$cats = $this->find('threaded', array('conditions' => $conditions, 'fields' => array(
				$this->alias . '.' . $this->primaryKey,
				$this->alias . '.' . $this->displayField,
				$this->alias . '.parent_id')));
		return $this->_generateNestedList($cats, $indent);
	}

	/**
	 * From http://othy.wordpress.com/2006/06/03/generatenestedlist/
	 *
	 * @deprecated use generateTreeList instead
	 */
	public function _generateNestedList($cats, $indent = '--', $level = 0) {
		static $list = array();
		$c = count($cats);
		for ($i = 0; $i < $c; $i++) {
			$list[$cats[$i][$this->alias][$this->primaryKey]] = str_repeat($indent, $level) . $cats[$i][$this->alias][$this->displayField];
			if (!empty($cats[$i]['children'])) {
				$this->_generateNestedList($cats[$i]['children'], $indent, $level + 1);
			}
		}
		return $list;
	}

}
