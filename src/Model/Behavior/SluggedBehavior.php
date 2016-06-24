<?php

namespace Tools\Model\Behavior;

use Cake\Core\Configure;
use Cake\Event\Event;
use Cake\ORM\Behavior;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\Table;
use Cake\Utility\Inflector;
use Exception;
use InvalidArgumentException;

/**
 * SluggedBehavior
 * Part based/inspired by the sluggable behavior of Mariano Iglesias
 *
 * Usage: See docs
 *
 * @author Andy Dawson
 * @author Mark Scherer
 * @license MIT
 */
class SluggedBehavior extends Behavior {

	/**
	 * Default config
	 *
	 * - label:
	 *     set to the name of a field to use for the slug, an array of fields to use as slugs or leave as null to rely
	 *     on the format returned by find('list') to determine the string to use for slugs
	 * - field: The slug field name
	 * - overwriteField: The boolean field to trigger overwriting if "overwrite" is false
	 * - mode: has the following values
	 *     ascii - retuns an ascii slug generated using the core Inflector::slug() function
	 *     display - a dummy mode which returns a slug legal for display - removes illegal (not unprintable) characters
	 *     url - returns a slug appropriate to put in a URL
	 *     class - a dummy mode which returns a slug appropriate to put in a html class (there are no restrictions)
	 *     id - retuns a slug appropriate to use in a html id
	 * - separator: The separator to use
	 * - length:
	 *  Set to 0 for no length. Will be auto-detected if possible via schema.
	 * - overwrite: has 2 values
	 *     false - once the slug has been saved, do not change it (use if you are doing lookups based on slugs)
	 *     true - if the label field values change, regenerate the slug (use if you are the slug is just window-dressing)
	 * - unique: has 2 values
	 *     false - will not enforce a unique slug, whatever the label is is direclty slugged without checking for duplicates
	 *     true - use if you are doing lookups based on slugs (see overwrite)
	 * - case: has the following values
	 *     null - don't change the case of the slug
	 *     low - force lower case. E.g. "this-is-the-slug"
	 *     up - force upper case E.g. "THIS-IS-THE-SLUG"
	 *     title - force title case. E.g. "This-Is-The-Slug"
	 *     camel - force CamelCase. E.g. "ThisIsTheSlug"
	 * - replace: custom replacements as array
	 * - on: beforeSave or beforeRules
	 * - scope: certain conditions to use as scope
	 * - tidy: If cleanup should be run on slugging
	 *
	 * @var array
	 */
	protected $_defaultConfig = [
		'label' => null,
		'field' => 'slug',
		'overwriteField' => 'overwrite_slug',
		'mode' => 'url',
		'separator' => '-',
		'length' => null,
		'overwrite' => false,
		'unique' => false,
		'notices' => true,
		'case' => null,
		'replace' => [
			'&' => 'and',
			'+' => 'and',
			'#' => 'hash',
		],
		'on' => 'beforeRules',
		'scope' => [],
		'tidy' => true,
		'implementedFinders' => ['slugged' => 'findSlugged'],
		//'implementedMethods' => ['slug' => 'slug']
	];

	/**
	 * Table instance
	 *
	 * @var \Cake\ORM\Table
	 */
	protected $_table;

	public function __construct(Table $table, array $config = []) {
		$this->_defaultConfig['notices'] = Configure::read('debug');
		$this->_defaultConfig['label'] = $table->displayField();
		foreach ($this->_defaultConfig['replace'] as $key => $value) {
			$this->_defaultConfig['replace'][$key] = __d('tools', $value);
		}
		$config += (array)Configure::read('Slugged');

		parent::__construct($table, $config);
	}

	/**
	 * Constructor hook method.
	 *
	 * Implement this method to avoid having to overwrite
	 * the constructor and call parent.
	 *
	 * @param array $config The configuration array this behavior is using.
	 * @return void
	 */
	public function initialize(array $config) {
		if ($this->_config['length'] === null) {
			$length = $this->_table->schema()->column($this->_config['field'])['length'];
			$this->_config['length'] = $length ?: 0;
		}

		$label = $this->_config['label'] = (array)$this->_config['label'];

		if ($this->_table->behaviors()->has('Translate')) {
			$this->_config['length'] = false;
		}
		if ($this->_config['length']) {
			foreach ($label as $field) {
				$alias = $this->_table->alias();
				if (strpos($field, '.')) {
					list($alias, $field) = explode('.', $field);
					if (!$this->_table->$alias->hasField($field)) {
						throw new Exception('(SluggedBehavior::setup) model ' . $this->_table->$alias->name . ' is missing the field ' . $field .
							' (specified in the setup for model ' . $this->_table->name . ') ');
					}
				} elseif (!$this->_table->hasField($field) && !method_exists($this->_table->entityClass(), '_get' . Inflector::classify($field))) {
					throw new Exception('(SluggedBehavior::setup) model ' . $this->_table->name . ' is missing the field ' . $field . ' specified in the setup.');
				}
			}
		}
	}

	/**
	 * SluggedBehavior::findSlugged()
	 *
	 * @param \Cake\ORM\Query $query
	 * @param array $options
	 * @return \Cake\ORM\Query
	 * @throws \InvalidArgumentException If the 'slug' key is missing in options
	 */
	public function findSlugged(Query $query, array $options) {
		if (empty($options['slug'])) {
			throw new InvalidArgumentException("The 'slug' key is required for find('slugged')");
		}

		return $query->where([$this->_config['field'] => $options['slug']]);
	}

	/**
	 * SluggedBehavior::beforeRules()
	 *
	 * @param \Cake\Event\Event $event
	 * @param \Cake\ORM\Entity $entity
	 * @return void
	 */
	public function beforeRules(Event $event, Entity $entity) {
		if ($this->_config['on'] === 'beforeRules') {
			$this->slug($entity);
		}
	}

	/**
	 * SluggedBehavior::beforeSave()
	 *
	 * @param \Cake\Event\Event $event
	 * @param \Cake\ORM\Entity $entity
	 * @return void
	 */
	public function beforeSave(Event $event, Entity $entity) {
		if ($this->_config['on'] === 'beforeSave') {
			$this->slug($entity);
		}
	}

	/**
	 * SluggedBehavior::slug()
	 *
	 * @param \Cake\ORM\Entity $entity Entity
	 * @param array $options Options
	 * @return void
	 */
	public function slug(Entity $entity, array $options = []) {
		$overwrite = isset($options['overwrite']) ? $options['overwrite'] : $this->_config['overwrite'];
		if (!$overwrite && $entity->get($this->_config['overwriteField'])) {
			$overwrite = true;
		}
		if ($overwrite || $entity->isNew() || !$entity->get($this->_config['field'])) {
			$slug = [];
			foreach ((array)$this->_config['label'] as $v) {
				$v = $entity->get($v);
				if ($v !== null && $v !== '') {
					$slug[] = $v;
				}
			}
			$slug = implode($slug, $this->_config['separator']);
			$slug = $this->generateSlug($slug, $entity);
			$entity->set($this->_config['field'], $slug);
		}
	}

	/**
	 * Method to find out if the current slug needs updating.
	 *
	 * The deep option is useful if you cannot rely on dirty() because
	 * of maybe some not in sync slugs anymore (saving the same title again,
	 * but the slug is completely different, for example).
	 *
	 * @param \Cake\ORM\Entity $entity
	 * @param bool $deep If true it will generate a new slug and compare it to the currently stored one.
	 * @return bool
	 */
	public function needsSlugUpdate($entity, $deep = false) {
		foreach ((array)$this->_config['label'] as $label) {
			if ($entity->dirty($label)) {
				return true;
			}
		}
		if ($deep) {
			$copy = clone $entity;
			$this->slug($copy, ['overwrite' => true]);
			return $copy->get($this->_config['field']) !== $entity->get($this->_config['field']);
		}
		return false;
	}

	/**
	 * Slug method
	 *
	 * For the given string, generate a slug. The replacements used are based on the mode setting, If tidy is false
	 * (only possible if directly called - primarily for tracing and testing) separators will not be cleaned up
	 * and so slugs like "-----as---df-----" are possible, which by default would otherwise be returned as "as-df".
	 * If the mode is "id" and the first charcter of the regex-ed slug is numeric, it will be prefixed with an x.
	 * If unique is set to true, check for a unique slug and if unavailable suffix the slug with -1, -2, -3 etc.
	 * until a unique slug is found
	 *
	 * @param string $value
	 * @param \Cake\ORM\Entity|null $entity
	 * @return string A slug
	 */
	public function generateSlug($value, Entity $entity = null) {
		$separator = $this->_config['separator'];

		$string = str_replace(["\r\n", "\r", "\n"], ' ', $value);
		$replace = $this->_config['replace'];
		if ($replace) {
			$string = str_replace(array_keys($replace), array_values($replace), $string);
		}
		if ($this->_config['mode'] === 'ascii') {
			$slug = Inflector::slug($string, $separator);
		} else {
			$regex = $this->_regex($this->_config['mode']);
			if ($regex) {
				$slug = $this->_pregReplace('@[' . $regex . ']@Su', $separator, $string);
			} else {
				$slug = $string;
			}
		}
		if ($this->_config['tidy']) {
			$slug = $this->_pregReplace('/' . $separator . '+/', $separator, $slug);
			$slug = trim($slug, $separator);
			if ($slug && $this->_config['mode'] === 'id' && is_numeric($slug[0])) {
				$slug = 'x' . $slug;
			}
		}
		if ($this->_config['length'] && (mb_strlen($slug) > $this->_config['length'])) {
			$slug = mb_substr($slug, 0, $this->_config['length']);
		}
		if ($this->_config['case']) {
			$case = $this->_config['case'];
			if ($case === 'up') {
				$slug = mb_strtoupper($slug);
			} else {
				$slug = mb_strtolower($slug);
			}
			if (in_array($case, ['title', 'camel'])) {
				$words = explode($separator, $slug);
				foreach ($words as $i => &$word) {
					$firstChar = mb_substr($word, 0, 1);
					$rest = mb_substr($word, 1, mb_strlen($word) - 1);
					$firstCharUp = mb_strtoupper($firstChar);
					$word = $firstCharUp . $rest;
				}
				if ($case === 'title') {
					$slug = implode($words, $separator);
				} elseif ($case === 'camel') {
					$slug = implode($words);
				}
			}
		}
		if ($this->_config['unique']) {
			if (!$entity) {
				throw new Exception('Needs an Entity to work on');
			}
			$field = $this->_table->alias() . '.' . $this->_config['field'];
			$conditions = [$field => $slug];
			$conditions = array_merge($conditions, $this->_config['scope']);
			$id = $entity->get($this->_table->primaryKey());
			if ($id) {
				$conditions['NOT'][$this->_table->alias() . '.' . $this->_table->primaryKey()] = $id;
			}
			$i = 0;
			$suffix = '';

			while ($this->_table->exists($conditions)) {
				$i++;
				$suffix	= $separator . $i;
				if ($this->_config['length'] && (mb_strlen($slug . $suffix) > $this->_config['length'])) {
					$slug = mb_substr($slug, 0, $this->_config['length'] - mb_strlen($suffix));
				}
				$conditions[$field] = $slug . $suffix;
			}
			if ($suffix) {
				$slug .= $suffix;
			}
		}

		return $slug;
	}

	/**
	 * ResetSlugs method.
	 *
	 * Regenerate all slugs. On large dbs this can take more than 30 seconds - a time
	 * limit is set to allow a minimum 100 updates per second as a preventative measure.
	 *
	 * Note that you should use the Reset behavior if you need additional functionality such
	 * as callbacks or timeouts.
	 *
	 * @param array $params
	 * @return bool Success
	 */
	public function resetSlugs($params = []) {
		if (!$this->_table->hasField($this->_config['field'])) {
			throw new Exception('Table does not have field ' . $this->_config['field']);
		}
		$defaults = [
			'page' => 1,
			'limit' => 100,
			'fields' => array_merge([$this->_table->primaryKey()], $this->_config['label']),
			'order' => $this->_table->displayField() . ' ASC',
			'conditions' => $this->_config['scope'],
			'overwrite' => true,
		];
		$params = array_merge($defaults, $params);
		$count = $this->_table->find('all', compact('conditions'))->count();
		$max = ini_get('max_execution_time');
		if ($max) {
			set_time_limit(max($max, $count / 100));
		}

		$this->_table->behaviors()->Slugged->config($params, null, false);
		while (($records = $this->_table->find('all', $params)->toArray())) {
			foreach ($records as $record) {
				$record->isNew(true);
				$options = [
					'validate' => true,
					'fieldList' => array_merge([$this->_table->primaryKey(), $this->_config['field']], $this->_config['label'])
				];
				if (!$this->_table->save($record, $options)) {
					throw new Exception(print_r($this->_table->errors(), true));
				}
			}
			$params['page']++;
		}
		return true;
	}

	/**
	 * Multi slug method
	 *
	 * Handle both slug and label fields using the translate behavior, and being edited
	 * in multiple locales at once
	 *
	 * //FIXME
	 *
	 * @param \Cake\ORM\Entity $entity
	 * @return void
	 */
	protected function _multiSlug(Entity $entity) {
		extract($this->_config);
		$field = current($label);
		$fields = (array)$entity->get($field);

		$locale = [];
		foreach ($fields as $locale => $_) {
			foreach ($label as $field) {
				$res = $entity->get($field);
				if (is_array($entity->get($field))) {
					$res = $this->generateSlug($field[$locale], $entity);
				}
			}
			//$this->beforeRules($entity);
			$locale[$locale] = $res;
		}
		$entity->set($slugField, $locale);
	}

	/**
	 * Wrapper for preg replace taking care of encoding
	 *
	 * @param string|array $pattern
	 * @param string|array $replace
	 * @param string $string
	 * @return string
	 */
	protected function _pregReplace($pattern, $replace, $string) {
		return preg_replace($pattern, $replace, $string);
	}

	/**
	 * Regex method
	 *
	 * Based upon the mode return a partial regex to generate a valid string for the intended use. Note that you
	 * can use almost litterally anything in a url - the limitation is only in what your own application
	 * understands. See the test case for info on how these regex patterns were generated.
	 *
	 * @param string $mode
	 * @return string|null A partial regex or false on failure
	 */
	protected function _regex($mode) {
		$return = '\x00-\x1f\x26\x3c\x7f-\x9f\x{fffe}-\x{ffff}';
		if ($mode === 'display') {
			return $return;
		}
		$return .= preg_quote(' \'"/?<>.$/:;?@=+&%\#', '@');
		if ($mode === 'url') {
			return $return;
		}
		$return .= '';
		if ($mode === 'class') {
			return $return;
		}
		if ($mode === 'id') {
			return '\x{0000}-\x{002f}\x{003a}-\x{0040}\x{005b}-\x{005e}\x{0060}\x{007b}-\x{007e}\x{00a0}-\x{00b6}' .
			'\x{00b8}-\x{00bf}\x{00d7}\x{00f7}\x{0132}-\x{0133}\x{013f}-\x{0140}\x{0149}\x{017f}\x{01c4}-\x{01cc}' .
			'\x{01f1}-\x{01f3}\x{01f6}-\x{01f9}\x{0218}-\x{024f}\x{02a9}-\x{02ba}\x{02c2}-\x{02cf}\x{02d2}-\x{02ff}' .
			'\x{0346}-\x{035f}\x{0362}-\x{0385}\x{038b}\x{038d}\x{03a2}\x{03cf}\x{03d7}-\x{03d9}\x{03db}\x{03dd}\x{03df}' .
			'\x{03e1}\x{03f4}-\x{0400}\x{040d}\x{0450}\x{045d}\x{0482}\x{0487}-\x{048f}\x{04c5}-\x{04c6}\x{04c9}-\x{04ca}' .
			'\x{04cd}-\x{04cf}\x{04ec}-\x{04ed}\x{04f6}-\x{04f7}\x{04fa}-\x{0530}\x{0557}-\x{0558}\x{055a}-\x{0560}' .
			'\x{0587}-\x{0590}\x{05a2}\x{05ba}\x{05be}\x{05c0}\x{05c3}\x{05c5}-\x{05cf}\x{05eb}-\x{05ef}\x{05f3}-\x{0620}' .
			'\x{063b}-\x{063f}\x{0653}-\x{065f}\x{066a}-\x{066f}\x{06b8}-\x{06b9}\x{06bf}\x{06cf}\x{06d4}\x{06e9}' .
			'\x{06ee}-\x{06ef}\x{06fa}-\x{0900}\x{0904}\x{093a}-\x{093b}\x{094e}-\x{0950}\x{0955}-\x{0957}' .
			'\x{0964}-\x{0965}\x{0970}-\x{0980}\x{0984}\x{098d}-\x{098e}\x{0991}-\x{0992}\x{09a9}\x{09b1}\x{09b3}-\x{09b5}' .
			'\x{09ba}-\x{09bb}\x{09bd}\x{09c5}-\x{09c6}\x{09c9}-\x{09ca}\x{09ce}-\x{09d6}\x{09d8}-\x{09db}\x{09de}' .
			'\x{09e4}-\x{09e5}\x{09f2}-\x{0a01}\x{0a03}-\x{0a04}\x{0a0b}-\x{0a0e}\x{0a11}-\x{0a12}\x{0a29}\x{0a31}\x{0a34}' .
			'\x{0a37}\x{0a3a}-\x{0a3b}\x{0a3d}\x{0a43}-\x{0a46}\x{0a49}-\x{0a4a}\x{0a4e}-\x{0a58}\x{0a5d}\x{0a5f}-\x{0a65}' .
			'\x{0a75}-\x{0a80}\x{0a84}\x{0a8c}\x{0a8e}\x{0a92}\x{0aa9}\x{0ab1}\x{0ab4}\x{0aba}-\x{0abb}\x{0ac6}\x{0aca}' .
			'\x{0ace}-\x{0adf}\x{0ae1}-\x{0ae5}\x{0af0}-\x{0b00}\x{0b04}\x{0b0d}-\x{0b0e}\x{0b11}-\x{0b12}\x{0b29}\x{0b31}' .
			'\x{0b34}-\x{0b35}\x{0b3a}-\x{0b3b}\x{0b44}-\x{0b46}\x{0b49}-\x{0b4a}\x{0b4e}-\x{0b55}\x{0b58}-\x{0b5b}\x{0b5e}' .
			'\x{0b62}-\x{0b65}\x{0b70}-\x{0b81}\x{0b84}\x{0b8b}-\x{0b8d}\x{0b91}\x{0b96}-\x{0b98}\x{0b9b}\x{0b9d}' .
			'\x{0ba0}-\x{0ba2}\x{0ba5}-\x{0ba7}\x{0bab}-\x{0bad}\x{0bb6}\x{0bba}-\x{0bbd}\x{0bc3}-\x{0bc5}\x{0bc9}' .
			'\x{0bce}-\x{0bd6}\x{0bd8}-\x{0be6}\x{0bf0}-\x{0c00}\x{0c04}\x{0c0d}\x{0c11}\x{0c29}\x{0c34}\x{0c3a}-\x{0c3d}' .
			'\x{0c45}\x{0c49}\x{0c4e}-\x{0c54}\x{0c57}-\x{0c5f}\x{0c62}-\x{0c65}\x{0c70}-\x{0c81}\x{0c84}\x{0c8d}\x{0c91}' .
			'\x{0ca9}\x{0cb4}\x{0cba}-\x{0cbd}\x{0cc5}\x{0cc9}\x{0cce}-\x{0cd4}\x{0cd7}-\x{0cdd}\x{0cdf}\x{0ce2}-\x{0ce5}' .
			'\x{0cf0}-\x{0d01}\x{0d04}\x{0d0d}\x{0d11}\x{0d29}\x{0d3a}-\x{0d3d}\x{0d44}-\x{0d45}\x{0d49}\x{0d4e}-\x{0d56}' .
			'\x{0d58}-\x{0d5f}\x{0d62}-\x{0d65}\x{0d70}-\x{0e00}\x{0e2f}\x{0e3b}-\x{0e3f}\x{0e4f}\x{0e5a}-\x{0e80}\x{0e83}' .
			'\x{0e85}-\x{0e86}\x{0e89}\x{0e8b}-\x{0e8c}\x{0e8e}-\x{0e93}\x{0e98}\x{0ea0}\x{0ea4}\x{0ea6}\x{0ea8}-\x{0ea9}' .
			'\x{0eac}\x{0eaf}\x{0eba}\x{0ebe}-\x{0ebf}\x{0ec5}\x{0ec7}\x{0ece}-\x{0ecf}\x{0eda}-\x{0f17}\x{0f1a}-\x{0f1f}' .
			'\x{0f2a}-\x{0f34}\x{0f36}\x{0f38}\x{0f3a}-\x{0f3d}\x{0f48}\x{0f6a}-\x{0f70}\x{0f85}\x{0f8c}-\x{0f8f}\x{0f96}' .
			'\x{0f98}\x{0fae}-\x{0fb0}\x{0fb8}\x{0fba}-\x{109f}\x{10c6}-\x{10cf}\x{10f7}-\x{10ff}\x{1101}\x{1104}\x{1108}' .
			'\x{110a}\x{110d}\x{1113}-\x{113b}\x{113d}\x{113f}\x{1141}-\x{114b}\x{114d}\x{114f}\x{1151}-\x{1153}' .
			'\x{1156}-\x{1158}\x{115a}-\x{115e}\x{1162}\x{1164}\x{1166}\x{1168}\x{116a}-\x{116c}\x{116f}-\x{1171}\x{1174}' .
			'\x{1176}-\x{119d}\x{119f}-\x{11a7}\x{11a9}-\x{11aa}\x{11ac}-\x{11ad}\x{11b0}-\x{11b6}\x{11b9}\x{11bb}' .
			'\x{11c3}-\x{11ea}\x{11ec}-\x{11ef}\x{11f1}-\x{11f8}\x{11fa}-\x{1dff}\x{1e9c}-\x{1e9f}\x{1efa}-\x{1eff}' .
			'\x{1f16}-\x{1f17}\x{1f1e}-\x{1f1f}\x{1f46}-\x{1f47}\x{1f4e}-\x{1f4f}\x{1f58}\x{1f5a}\x{1f5c}\x{1f5e}' .
			'\x{1f7e}-\x{1f7f}\x{1fb5}\x{1fbd}\x{1fbf}-\x{1fc1}\x{1fc5}\x{1fcd}-\x{1fcf}\x{1fd4}-\x{1fd5}\x{1fdc}-\x{1fdf}' .
			'\x{1fed}-\x{1ff1}\x{1ff5}\x{1ffd}-\x{20cf}\x{20dd}-\x{20e0}\x{20e2}-\x{2125}\x{2127}-\x{2129}' .
			'\x{212c}-\x{212d}\x{212f}-\x{217f}\x{2183}-\x{3004}\x{3006}\x{3008}-\x{3020}\x{3030}\x{3036}-\x{3040}' .
			'\x{3095}-\x{3098}\x{309b}-\x{309c}\x{309f}-\x{30a0}\x{30fb}\x{30ff}-\x{3104}\x{312d}-\x{4dff}' .
			'\x{9fa6}-\x{abff}\x{d7a4}-\x{d7ff}\x{e000}-\x{ffff}';
		}
		return null;
	}

}
