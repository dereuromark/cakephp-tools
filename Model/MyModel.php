<?php
App::uses('ShimModel', 'Shim.Model');
App::uses('Utility', 'Tools.Utility');
App::uses('Hash', 'Utility');

/**
 * Model enhancements for Cake2
 *
 * @author Mark Scherer
 * @license http://opensource.org/licenses/mit-license.php MIT
 */
class MyModel extends ShimModel {

	/**
	 * MyModel::__construct()
	 *
	 * @param int $id
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
			Cache::config('sql', [
				'engine' => 'File',
				'serialize' => true,
				'prefix'	=> '',
				'path' => CACHE . 'sql' . DS,
				'duration'	=> '+1 day'
			]);
		}

		// Get a notice if there is an AppModel instance instead of a real Model (in those cases usually a dev error!)
		if (!is_a($this, $this->name) && $this->displayField !== $this->primaryKey && $this->useDbConfig === 'default'
			&& !Configure::read('Core.disableModelInstanceNotice')) {
			trigger_error('AppModel instance! Expected: ' . $this->name);
		}
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
			$newOptions = [];
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
	public function virtualFields($fields = []) {
		$res = [];
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
	public function up($id, $customOptions = []) {
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
			$currentValue = $this->field($field, [$this->alias . '.id' => $id]);
			if ($currentValue === false) {
				return false;
			}
		} else {
			$currentValue = $customOptions['current'];
		}

		$value = (int)$currentValue + (int)$step;
		$data = [$field => $value];
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
	 * @return int next auto increment value or False on failure
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
	 * @return bool Success
	 */
	public function saveAll($data = null, $options = []) {
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
	 * @return bool Success
	 */
	public function beforeValidate($options = []) {
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
	 * @return bool Success
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
	 * @param bool $parenthesise Add parenthesis before and after
	 * @return string result sql snippet of the query to run
	 * @modified Mark Scherer (cake2.x ready and improvements)
	 * @link http://bakery.cakephp.org/articles/lucaswxp/2011/02/11/easy_and_simple_subquery_cakephp
	 */
	public function subquery($type, $options = [], $alias = null, $parenthesise = true) {
		if ($alias === null) {
			$alias = 'Sub' . $this->alias . '';
		}

		$fields = [$alias . '.id'];
		$limit = null;
		switch ($type) {
			case 'count':
				$fields = ['COUNT(*)'];
				break;
			case 'first':
				$limit = 1;
				break;
		}

		$dbo = $this->getDataSource();

		$default = [
			'fields' => $fields,
			'table' => $dbo->fullTableName($this),
			'alias' => $alias,
			'limit' => $limit,
			'offset' => null,
			'joins' => [],
			'conditions' => [],
			'order' => null,
			'group' => null
		];
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
	public function find($type = null, $query = []) {
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

		// having and group clauses enhancement
		if (is_array($query) && !empty($query['having']) && !empty($query['group'])) {
			if (!is_array($query['group'])) {
				$query['group'] = [$query['group']];
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

			$options = ['prefix' => strtolower(Inflector::underscore($this->alias)) . '__', ];
			if (!empty($expires)) {
				$options['duration'] = $expires;
			}
			if (!Configure::read('Cache.disable')) {
				Cache::config('sql', $options);

				$key = Inflector::underscore($key);
				$results = Cache::read($key, 'sql');
			}

			if (!isset($results)) {
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
	protected function _find($type, $options = []) {
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
						$options['joins'] = [];
					}

					if (!isset($options['model']) || !isset($options['scope'])) {
						break;
					}
					$assoc = $this->hasAndBelongsToMany[$options['model']];
					$bind = "{$assoc['with']}.{$assoc['foreignKey']} = {$this->alias}.{$this->primaryKey}";

					$options['joins'][] = [
						'table' => $assoc['joinTable'],
						'alias' => $assoc['with'],
						'type' => 'inner',
						'foreignKey' => false,
						'conditions' => [$bind]
					];

					$bind = $options['model'] . '.' . $this->{$options['model']}->primaryKey . ' = ';
					$bind .= "{$assoc['with']}.{$assoc['associationForeignKey']}";

					$options['joins'][] = [
						'table' => $this->{$options['model']}->table,
						'alias' => $options['model'],
						'type' => 'inner',
						'foreignKey' => false,
						'conditions' => [$bind] + (array)$options['scope'],
					];
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
						for ($i = 0; $i < (count($options['fields']) - 1); $i++) {
							$format .= '%s ';
						}

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
					$res = Hash::combine($list, '{n}.' . $this->alias . '.' . $this->primaryKey, $tmpPath2);
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
	protected function _findNeighbors($state, $query, $results = []) {
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
	public function neighbors($id = null, $options = [], $qryOptions = []) {
		$sortField = (!empty($options['field']) ? $options['field'] : 'created');
		$normalDirection = (!empty($options['reverse']) ? false : true);
		$sortDirWord = $normalDirection ? ['ASC', 'DESC'] : ['DESC', 'ASC'];
		$sortDirSymb = $normalDirection ? ['>=', '<='] : ['<=', '>='];

		$displayField = (!empty($options['displayField']) ? $options['displayField'] : $this->displayField);

		if (is_array($id)) {
			$data = $id;
			$id = $data[$this->alias][$this->primaryKey];
		} elseif ($id === null) {
			$id = $this->id;
		}
		if (!empty($id)) {
			$data = $this->find('first', ['conditions' => [$this->primaryKey => $id], 'contain' => []]);
		}

		if (empty($id) || empty($data) || empty($data[$this->alias][$sortField])) {
			return [];
		} else {
			$field = $data[$this->alias][$sortField];
		}
		$findOptions = ['recursive' => -1];
		if (isset($qryOptions['recursive'])) {
			$findOptions['recursive'] = $qryOptions['recursive'];
		}
		if (isset($qryOptions['contain'])) {
			$findOptions['contain'] = $qryOptions['contain'];
		}

		$findOptions['fields'] = [$this->alias . '.' . $this->primaryKey, $this->alias . '.' . $displayField];
		$findOptions['conditions'][$this->alias . '.' . $this->primaryKey . ' !='] = $id;

		// //TODO: take out
		if (!empty($options['filter']) && $options['filter'] == REQUEST_STATUS_FILTER_OPEN) {
			$findOptions['conditions'][$this->alias . '.status <'] = REQUEST_STATUS_DECLINED;
		} elseif (!empty($options['filter']) && $options['filter'] == REQUEST_STATUS_FILTER_CLOSED) {
			$findOptions['conditions'][$this->alias . '.status >='] = REQUEST_STATUS_DECLINED;
		}

		$return = [];

		if (!empty($qryOptions['conditions'])) {
			$findOptions['conditions'] = Hash::merge($findOptions['conditions'], $qryOptions['conditions']);
		}

		$options = $findOptions;
		$options['conditions'] = Hash::merge($options['conditions'], [$this->alias . '.' . $sortField . ' ' . $sortDirSymb[1] => $field]);
		$options['order'] = [$this->alias . '.' . $sortField . '' => $sortDirWord[1]];
		$this->id = $id;
		$return['prev'] = $this->find('first', $options);

		$options = $findOptions;
		$options['conditions'] = Hash::merge($options['conditions'], [$this->alias . '.' . $sortField . ' ' . $sortDirSymb[0] => $field]);
		$options['order'] = [$this->alias . '.' . $sortField . '' => $sortDirWord[0]]; // ??? why 0 instead of 1
		$this->id = $id;
		$return['next'] = $this->find('first', $options);

		return $return;
	}

	/**
	 * Validates a primary or foreign key depending on the current schema data for this field
	 * recognizes uuid (char36) and aiid (int10 unsigned) - not yet mixed (varchar36)
	 * more useful than using numeric or notEmpty which are type specific
	 *
	 * @param array $data
	 * @param array $options
	 * - allowEmpty
	 * @return bool Success
	 */
	public function validateKey($data = [], $options = []) {
		$keys = array_keys($data);
		$key = array_shift($keys);
		$value = array_shift($data);

		$schema = $this->schema($key);
		if (!$schema) {
			return true;
		}

		$defaults = [
			'allowEmpty' => false,
		];
		$options += $defaults;

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
	 * @return bool Success
	 */
	public function validateEnum(array $data, $enum = null, $additionalKeys = []) {
		$keys = array_keys($data);
		$valueKey = array_shift($keys);
		$value = $data[$valueKey];
		$keys = [];
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
	 * @return bool Success
	 */
	public function validateIdentical($data = [], $compareWith = null, $options = []) {
		if (is_array($data)) {
			$value = array_shift($data);
		} else {
			$value = $data;
		}
		$compareValue = $this->data[$this->alias][$compareWith];

		$matching = ['string' => 'string', 'int' => 'integer', 'float' => 'float', 'bool' => 'boolean'];
		if (!empty($options['cast']) && array_key_exists($options['cast'], $matching)) {
			// cast values to string/int/float/bool if desired
			settype($compareValue, $matching[$options['cast']]);
			settype($value, $matching[$options['cast']]);
		}
		return ($compareValue === $value);
	}

	/**
	 * Validate range, but in a more sane way than CORE range().
	 * This range() validation rule is inclusive regarding the borders.
	 *
	 * If $lower and $upper are not set, will return true if
	 * $check is a legal finite on this platform
	 *
	 * @param string $check Value to check
	 * @param float $lower Lower limit
	 * @param float $upper Upper limit
	 * @return bool Success
	 */
	public function validateRange($data, $lower = null, $upper = null) {
		foreach ($data as $key => $check) {
			break;
		}
		if (!is_numeric($check)) {
			return false;
		}
		if (isset($lower) && isset($upper)) {
			return ($check >= $lower && $check <= $upper);
		}
		return is_finite($check);
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
	 * @return bool Success
	 */
	public function validateUnique($data, $fields = [], $options = []) {
		$id = (!empty($this->data[$this->alias][$this->primaryKey]) ? $this->data[$this->alias][$this->primaryKey] : 0);
		if (!$id && $this->id) {
			$id = $this->id;
		}

		foreach ($data as $key => $value) {
			$fieldName = $key;
			$fieldValue = $value;
			break;
		}

		$conditions = [
			$this->alias . '.' . $fieldName => $fieldValue,
			$this->alias . '.id !=' => $id];

		$fields = (array)$fields;
		if (!array_key_exists('allowEmpty', $fields)) {
			foreach ($fields as $dependingField) {
				if (isset($this->data[$this->alias][$dependingField])) { // add ONLY if some content is transfered (check on that first!)
					$conditions[$this->alias . '.' . $dependingField] = $this->data[$this->alias][$dependingField];
				} elseif (isset($this->data['Validation'][$dependingField])) { // add ONLY if some content is transfered (check on that first!
					$conditions[$this->alias . '.' . $dependingField] = $this->data['Validation'][$dependingField];
				} elseif (!empty($id)) {
					// manual query! (only possible on edit)
					$res = $this->find('first', ['fields' => [$this->alias . '.' . $dependingField], 'conditions' => [$this->alias . '.id' => $id]]);
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
		$options = ['fields' => [$this->alias . '.' . $this->primaryKey], 'conditions' => $conditions];
		$res = $this->find('first', $options);
		return empty($res);
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
	 * @return bool Success
	 */
	public function validateUrl($data, $options = []) {
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
	 * @return bool Success
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
	 * - min/max (defaults to >= 1 - at least 1 second apart)
	 * @return bool Success
	 */
	public function validateDateTime($data, $options = []) {
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
			$seconds = isset($options['min']) ? $options['min'] : 1;
			if (!empty($options['after']) && isset($this->data[$this->alias][$options['after']])) {
				if (strtotime($this->data[$this->alias][$options['after']]) > strtotime($value) - $seconds) {
					return false;
				}
			}
			if (!empty($options['before']) && isset($this->data[$this->alias][$options['before']])) {
				if (strtotime($this->data[$this->alias][$options['before']]) < strtotime($value) + $seconds) {
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
	 * @return bool Success
	 */
	public function validateDate($data, $options = []) {
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
	 * @return bool Success
	 */
	public function validateTime($data, $options = []) {
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
	public function validateDateRange($data, $options = []) {
	}

	/**
	 * Validation of Time Fields (>= minTime && <= maxTime)
	 *
	 * @param options
	 * - min/max (TODO!!)
	 */
	public function validateTimeRange($data, $options = []) {
	}

	/**
	 * Model validation rule for email addresses
	 *
	 * @return bool Success
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
	 * @return bool true if valid, else false
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
	 * @return bool ifNotBlacklisted
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
	public function set($data, $data2 = null, $requiredFields = [], $fieldList = []) {
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
			return [];
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
			return [];
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
		$guaranteedFields = [];
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
			$data = Hash::merge($guaranteedFields, $data);
		}
		return $data;
	}

	/**
	 * Make certain fields a requirement for the form to validate
	 * (they must only be present - can still be empty, though!)
	 *
	 * @param array $fieldList
	 * @param bool $allowEmpty (or NULL to not touch already set elements)
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
				$this->validate[$column]['notEmpty'] = ['rule' => 'notEmpty', 'required' => true, 'allowEmpty' => $setAllowEmpty, 'message' => 'valErrMandatoryField'];
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
	 * Wraps ShimModel::get() for an exception free response.
	 *
	 *   $record = $this->Model->get($id);
	 *
	 * @param mixed $id
	 * @param array $options Options for find().
	 * @return array
	 */
	public function record($id, array $options = []) {
		try {
			return $this->get($id, $options);
		} catch (RecordNotFoundException $e) {
		}
		return array();
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
	public function getRelatedInUse($modelName, $groupField = null, $type = 'all', $options = []) {
		if ($groupField === null) {
			$groupField = $this->belongsTo[$modelName]['foreignKey'];
		}
		$defaults = [
			'contain' => [$modelName],
			'group' => $groupField,
			'order' => $this->$modelName->order ? $this->$modelName->order : [$modelName . '.' . $this->$modelName->displayField => 'ASC'],
		];
		if ($type === 'list') {
			$defaults['fields'] = [$modelName . '.' . $this->$modelName->primaryKey, $modelName . '.' . $this->$modelName->displayField];
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
	public function getFieldInUse($groupField, $type = 'all', $options = []) {
		$defaults = [
			'group' => $groupField,
			'order' => [$this->alias . '.' . $this->displayField => 'ASC'],
		];
		if ($type === 'list') {
			$defaults['fields'] = [$this->alias . '.' . $this->primaryKey, $this->alias . '.' . $this->displayField];
		}
		$options += $defaults;
		return $this->find($type, $options);
	}

	/**
	 * Update a row with certain fields (dont use "Model" as super-key)
	 *
	 * @param int $id
	 * @param array $data
	 * @return bool|array Success
	 */
	public function update($id, $data, $validate = false) {
		$this->id = $id;
		$options = [
			'validate' => $validate,
			'fieldList' => array_keys($data)
		];
		return $this->save($data, $options);
	}

	/**
	 * Toggles Field (Important/Deleted/Primary etc)
	 *
	 * @param STRING fieldName
	 * @param int id (cleaned!)
	 * @return ARRAY record: [Model][values],...
	 */
	public function toggleField($fieldName, $id) {
		$record = $this->get($id, ['conditions' => [$this->primaryKey, $fieldName]]);

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
	 * @return bool Success
	 */
	public function truncate($table = null) {
		if (empty($table)) {
			$table = $this->table;
		}
		$db = ConnectionManager::getDataSource($this->useDbConfig);
		return $db->truncate($table);
	}

	/**
	 * Recursive Dropdown Lists
	 * NEEDS tree behavior, NEEDS lft, rght, parent_id (!)
	 * //FIXME
	 */
	public function recursiveSelect($conditions = [], $attachTree = false, $spacer = '-- ') {
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
		$cats = $this->find('threaded', ['conditions' => $conditions, 'fields' => [
				$this->alias . '.' . $this->primaryKey,
				$this->alias . '.' . $this->displayField,
				$this->alias . '.parent_id']]);
		return $this->_generateNestedList($cats, $indent);
	}

	/**
	 * From http://othy.wordpress.com/2006/06/03/generatenestedlist/
	 *
	 * @deprecated use generateTreeList instead
	 */
	public function _generateNestedList($cats, $indent = '--', $level = 0) {
		static $list = [];
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
