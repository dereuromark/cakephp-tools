<?php
/**
 * Part based/inspired by the sluggable behavior of Mariano Iglesias
 *
 * PHP version 5
 *
 * @copyright Copyright (c) 2008, Andy Dawson
 * @author Andy Dawson
 * @author Mark Scherer
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 */

App::uses('ModelBehavior', 'Model');

/**
 * SluggedBehavior
 *
 */
class SluggedBehavior extends ModelBehavior {

	/**
	 * Default settings
	 *
	 * label
	 * 	set to the name of a field to use for the slug, an array of fields to use as slugs or leave as null to rely
	 * 	on the format returned by find('list') to determine the string to use for slugs
	 * overwrite has 2 values
	 * 	false - once the slug has been saved, do not change it (use if you are doing lookups based on slugs)
	 * 	true - if the label field values change, regenerate the slug (use if you are the slug is just window-dressing)
	 * unique has 2 values
	 * 	false - will not enforce a unique slug, whatever the label is is direclty slugged without checking for duplicates
	 * 	true - use if you are doing lookups based on slugs (see overwrite)
	 * mode has the following values
	 * 	ascii - retuns an ascii slug generated using the core Inflector::slug() function
	 * 	display - a dummy mode which returns a slug legal for display - removes illegal (not unprintable) characters
	 * 	url - returns a slug appropriate to put in a URL
	 * 	class - a dummy mode which returns a slug appropriate to put in a html class (there are no restrictions)
	 * 	id - retuns a slug appropriate to use in a html id
	 * case has the following values
	 * 	null - don't change the case of the slug
	 * 	low - force lower case. E.g. "this-is-the-slug"
	 * 	up - force upper case E.g. "THIS-IS-THE-SLUG"
	 * 	title - force title case. E.g. "This-Is-The-Slug"
	 * 	camel - force CamelCase. E.g. "ThisIsTheSlug"
	 *
	 * @var array
	 */
	protected $_defaultSettings = array(
		'label' => null,
		'slugField' => 'slug',
		'overwriteField' => 'overwrite_slug',
		'mode' => 'url',
		'separator' => '-',
		'defaultSuffix' => null,
		'length' => 100,
		'overwrite' => false,
		'unique' => false,
		'notices' => true,
		'case' => null,
		'replace' => array(
			'&' => 'and',
			'+' => 'and',
			'#' => 'hash',
		),
		'run' => 'beforeValidate',
		'language' => null,
		'encoding' => null,
		'trigger' => false,
		'scope' => array()
	);

	/**
	 * StopWords property
	 *
	 * A (3 letter) language code indexed array of stop words
	 *
	 * @var array
	 */
	public $stopWords = array();

	/**
	 * Setup method
	 *
	 * Use the model's label field as the default field on which to base the slug, the label can be made up of multiple
	 * fields by specifying an array of fields
	 *
	 * @param Model $Model
	 * @param array $config
	 * @return void
	 */
	public function setup(Model $Model, $config = array()) {
		$this->_defaultSettings['notices'] = Configure::read('debug');
		$this->_defaultSettings['label'] = array($Model->displayField);
		foreach ($this->_defaultSettings['replace'] as $key => $value) {
			$this->_defaultSettings['replace'][$key] = __($value);
		}
		$this->_defaultSettings = array_merge($this->_defaultSettings, (array)Configure::read('Slugged'));

		$this->settings[$Model->alias] = array_merge($this->_defaultSettings, $config);
		extract($this->settings[$Model->alias]);
		$label = $this->settings[$Model->alias]['label'] = (array)$label;
		if ($Model->Behaviors->loaded('Translate')) {
			$notices = false;
		}
		if ($notices) {
			foreach ($label as $field) {
				$alias = $Model->alias;
				if (strpos($field, '.')) {
					list($alias, $field) = explode('.', $field);
					if (!$Model->$alias->hasField($field)) {
						trigger_error('(SluggedBehavior::setup) model ' . $Model->$alias->name . ' is missing the field ' . $field .
							' (specified in the setup for model ' . $Model->name . ') ', E_USER_WARNING);
						$Model->Behaviors->disable($this->name);
					}
				} elseif (!$Model->hasField($field)) {
					trigger_error('(SluggedBehavior::setup) model ' . $Model->name . ' is missing the field ' . $field . ' specified in the setup.', E_USER_WARNING);
					$Model->Behaviors->disable($this->name);
				}
			}
		}
	}

	/**
	 * BeforeValidate method
	 *
	 * @param Model $Model
	 * @return void
	 */
	public function beforeValidate(Model $Model, $options = array()) {
		extract($this->settings[$Model->alias]);
		if ($run !== 'beforeValidate') {
			return;
		}
		if (is_string($this->settings[$Model->alias]['trigger'])) {
			if (!$Model->{$this->settings[$Model->alias]['trigger']}) {
				return;
			}
		}
		$this->generateSlug($Model);
	}

	/**
	 * BeforeSave method
	 *
	 * @param Model $Model
	 * @return void
	 */
	public function beforeSave(Model $Model, $options = array()) {
		extract($this->settings[$Model->alias]);
		if ($run !== 'beforeSave') {
			return;
		}
		if (is_string($this->settings[$Model->alias]['trigger'])) {
			if (!$Model->{$this->settings[$Model->alias]['trigger']}) {
				return true;
			}
		}
		$this->generateSlug($Model);
	}

	/**
	 * Generate slug method
	 *
	 * if a new row, or overwrite is set to true, check for a change to a label field and add the slug to the data
	 * to be saved
	 * If no slug at all is returned (should not be permitted and prevented by validating the label fields) the model
	 * alias will be used as a slug.
	 * If unique is set to true, check for a unique slug and if unavailable suffix the slug with -1, -2, -3 etc.
	 * until a unique slug is found
	 *
	 * @param Model $Model
	 * @return void
	 */
	public function generateSlug(Model $Model) {
		extract($this->settings[$Model->alias]);
		if ($notices && !$Model->hasField($slugField)) {
			return;
		}
		if (!$overwrite && !empty($Model->data[$Model->alias][$overwriteField])) {
			$overwrite = true;
		}
		if ($overwrite || !$Model->id) {
			if ($label) {
				$somethingToDo = false;
				foreach ($label as $field) {
					$alias = $Model->alias;
					if (strpos($field, '.') !== false) {
						list($alias, $field) = explode('.', $field, 2);
					}
					if (isset($Model->data[$alias][$field])) {
						$somethingToDo = true;
					}
				}
				if (!$somethingToDo) {
					return;
				}
				$slug = array();
				foreach ($label as $field) {
					$alias = $Model->alias;
					if (strpos($field, '.')) {
						list($alias, $field) = explode('.', $field);
					}
					if (isset($Model->data[$alias][$field])) {
						if (is_array($Model->data[$alias][$field])) {
							return $this->_multiSlug($Model);
						}
						$slug[] = $Model->data[$alias][$field];
					} elseif ($Model->id) {
						$slug[] = $Model->field($field);
					}
				}
				$slug = implode($slug, $separator);
			} else {
				$slug = $this->display($Model);
			}
			$slug = $Model->slug($slug);
			if (!$slug) {
				$slug = $Model->alias;
			}
			if ($unique) {
				$conditions = array($Model->alias . '.' . $slugField => $slug);
				$conditions = array_merge($conditions, $this->settings[$Model->alias]['scope']);
				if ($Model->id) {
					$conditions['NOT'][$Model->alias . '.' . $Model->primaryKey] = $Model->id;
				}
				$i = 0;
				$suffix = '';

				while ($Model->hasAny($conditions)) {
					$i++;
					$suffix	= $separator . $i;
					if (strlen($slug . $suffix) > $length) {
						$slug = substr($slug, 0, $length - strlen($suffix));
					}
					$conditions[$Model->alias . '.' . $slugField] = $slug . $suffix;
				}
				if ($suffix) {
					$slug .= $suffix;
				}
			}
			$this->_addToWhitelist($Model, array($slugField));
			$Model->data[$Model->alias][$slugField] = $slug;
		}
	}

	/**
	 * RemoveStopWords from a string. if $splitOnStopWord is true, the following occurs:
	 * 	input "apples bananas pears and red cars"
	 * 	output array('apples bananas pears', 'red cars')
	 *
	 * If the passed string doesn't contain the separator, or after stripping out stop words there's
	 * nothing left - the original input is returned (in the desired format)
	 *
	 * Therefore passing "contain" will return immediately array('contain')
	 * Passing "contain this text" will return array('text')
	 * 	both contain and this are stop words
	 * Passing "contain this" will return array('contain this')
	 *
	 * @param Model $Model
	 * @param mixed $string string or array of words
	 * @param array $params
	 * @return mixed
	 */
	public function removeStopWords(Model $Model, $string = '', $params = array()) {
		if (!$string) {
			return $string;
		}
		$separator = ' ';
		$splitOnStopWord = true;
		$return = 'array';
		$originalIfEmpty = true;
		extract($params);

		if (!empty($this->settings[$Model->alias]['language'])) {
			$lang = $this->settings[$Model->alias]['language'];
		} else {
			$lang = Configure::read('Config.language');
			if (!$lang) {
				$lang = 'eng';
			}
			$this->settings[$Model->alias]['language'] = $lang;
		}

		if (!array_key_exists($lang, $this->stopWords)) {
			ob_start();
			if (!App::import('Vendor', 'stop_words_' . $lang, array('file' => "stop_words" . DS . "$lang.txt"))) {
				$res = App::import('Vendor', 'Tools.stop_words_' . $lang, array('file' => "stop_words" . DS . "$lang.txt"));
				if (!$res) {
					ob_get_clean();
					return $string;
				}
			}
			$stopWords = preg_replace('@/\*.*\*/@', '', ob_get_clean());
			$this->stopWords[$lang] = array_filter(array_map('trim', explode("\n", $stopWords)));
		}

		if (is_array($string)) {
			$originalTerms = $terms = $string;
			foreach ($terms as $i => &$term) {
				$term = trim(preg_replace('@[^\p{Ll}\p{Lm}\p{Lo}\p{Lt}\p{Lu}]@u', $separator, $term), $separator);
			}
			$lTerms = array_map('mb_strtolower', $terms);
			$lTerms = array_diff($lTerms, $this->stopWords[$lang]);
			$terms = array_intersect_key($terms, $lTerms);
		} else {
			if (!strpos($string, $separator)) {
				if ($return === 'array') {
					return array($string);
				}
				return $string;
			}
			$string = preg_replace('@[^\p{Ll}\p{Lm}\p{Lo}\p{Lt}\p{Lu}]@u', $separator, $string);
			$originalTerms = $terms = array_filter(array_map('trim', explode($separator, $string)));

			if ($splitOnStopWord) {
				$terms = $chunk = array();
				$snippet = '';
				foreach ($originalTerms as $term) {
					$lterm = strtolower($term);
					if (in_array($lterm, $this->stopWords[$lang])) {
						if ($chunk) {
							$terms[] = $chunk;
							$chunk = array();
						}
						continue;
					}
					$chunk[] = $term;
				}
				if ($chunk) {
					$terms[] = $chunk;
				}
				foreach ($terms as &$phrase) {
					$phrase = implode(' ', $phrase);
				}
			} else {
				$lTerms = array_map('mb_strtolower', $terms);
				$lTerms = array_diff($lTerms, $this->stopWords[$lang]);
				$terms = array_intersect_key($terms, $lTerms);
			}
		}

		if (!$terms && $originalIfEmpty) {
			$terms = array(implode(' ', $originalTerms));
		}
		if ($return === 'array') {
			return array_values(array_unique($terms));
		}
		return implode($separator, $terms);
	}

	/**
	 * Slug method
	 *
	 * For the given string, generate a slug. The replacements used are based on the mode setting, If tidy is false
	 * (only possible if directly called - primarily for tracing and testing) separators will not be cleaned up
	 * and so slugs like "-----as---df-----" are possible, which by default would otherwise be returned as "as-df".
	 * If the mode is "id" and the first charcter of the regex-ed slug is numeric, it will be prefixed with an x.
	 *
	 * @param Model $Model
	 * @param mixed $string
	 * @param boolean $tidy
	 * @return string a slug
	 */
	public function slug(Model $Model, $string, $tidy = true) {
		extract($this->settings[$Model->alias]);
		$this->_setEncoding($Model, $encoding, $string, !Configure::read('debug'));

		$string = str_replace(array("\r\n", "\r", "\n"), ' ', $string);
		if ($replace) {
			$string = str_replace(array_keys($replace), array_values($replace), $string);
		}
		if ($mode === 'ascii') {
			$slug = Inflector::slug($string, $separator);
		} else {
			$regex = $this->_regex($mode);
			if ($regex) {
				$slug = $this->_pregReplace('@[' . $regex . ']@Su', $separator, $string, $encoding);
			} else {
				$slug = $string;
			}
		}
		if ($tidy) {
			$slug = $this->_pregReplace('/' . $separator . '+/', $separator, $slug, $encoding);
			$slug = trim($slug, $separator);
			if ($slug && $mode === 'id' && is_numeric($slug[0])) {
				$slug = 'x' . $slug;
			}
		}
		if (strlen($slug) > $length) {
			$slug = mb_substr($slug, 0, $length);
			while ($slug && strlen($slug) > $length) {
				$slug = mb_substr($slug, 0, mb_strlen($slug) - 1);
			}
		}
		if ($case) {
			if ($case === 'up') {
				$slug = mb_strtoupper($slug);
			} else {
				$slug = mb_strtolower($slug);
			}
			if (in_array($case, array('title', 'camel'))) {
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

		return $slug;
	}

	/**
	 * Display method
	 *
	 * Cheat - use find('list') and assume it has been modified such that lists show in the desired format.
	 * First check (since this method is called in beforeSave) if there is data to be saved, and use that
	 * to get the display name
	 * Otherwise, read from the database
	 *
	 * @param mixed $id
	 * @return mixed string (the display name) or false
	 */
	public function display(Model $Model, $id = null) {
		if (!$id) {
			if (!$Model->id) {
				return false;
			}
			$id = $Model->id;
		}
		$conditions = array_merge(array(
			$Model->alias . '.' . $Model->primaryKey => $id),
			$this->settings[$Model->alias]['scope']);
		return current($Model->find('list', array('conditions' => $conditions)));
	}

	/**
	 * ResetSlugs method.
	 *
	 * Regenerate all slugs. On large dbs this can take more than 30 seconds - a time
	 * limit is set to allow a minimum 100 updates per second as a preventative measure.
	 *
	 * @param AppModel $Model
	 * @param array $conditions
	 * @param integer $recursive
	 * @return boolean Success
	 */
	public function resetSlugs(Model $Model, $params = array()) {
		$recursive = -1;
		extract($this->settings[$Model->alias]);
		if ($notices && !$Model->hasField($slugField)) {
			return false;
		}
		$defaults = array(
			'page' => 1,
			'limit' => 100,
			'fields' => array_merge(array($Model->primaryKey), $label),
			'order' => $Model->displayField . ' ASC',
			'conditions' => $scope,
			'recursive' => $recursive,
			'overwrite' => true,
		);
		$params = array_merge($defaults, $params);
		$count = $Model->find('count', compact('conditions'));
		$max = ini_get('max_execution_time');
		if ($max) {
			set_time_limit(max($max, $count / 100));
		}

		$settings = $Model->Behaviors->Slugged->settings[$Model->alias];
		$Model->Behaviors->load('Tools.Slugged', $params + $settings);

		while ($rows = $Model->find('all', $params)) {
			foreach ($rows as $row) {
				$Model->create();
				if (!$Model->save($row, true, array_merge(array($Model->primaryKey, $slugField), $label))) {
					throw new RuntimeException(print_r($row[$Model->alias], true) . ': ' . print_r($Model->validationErrors, true));
				}
			}
			$params['page']++;
		}
		return true;
	}

	/**
	 * Multi slug method
	 *
	 * Handle both slug and lable fields using the translate behavior, and being edited
	 * in multiple locales at once
	 *
	 * @param Model $Model
	 * @return void
	 */
	protected function _multiSlug(Model $Model) {
		extract($this->settings[$Model->alias]);
		$data = $Model->data;
		$field = current($label);
		foreach ($Model->data[$Model->alias][$field] as $locale => $_) {
			foreach ($label as $field) {
				if (is_array($data[$Model->alias][$field])) {
					$Model->data[$Model->alias][$field] = $data[$Model->alias][$field][$locale];
				}
			}
			$this->beforeValidate($Model);
			$data[$Model->alias][$slugField][$locale] = $Model->data[$Model->alias][$field];
		}
		$Model->data = $data;
	}

	/**
	 * Wrapper for preg replace taking care of encoding
	 *
	 * @param mixed $pattern
	 * @param mixed $replace
	 * @param mixed $string
	 * @param string $encoding
	 * @return void
	 */
	protected function _pregReplace($pattern, $replace, $string, $encoding = 'UTF-8') {
		if ($encoding && $encoding !== 'UTF-8') {
			$string = mb_convert_encoding($string, 'UTF-8', $encoding);
		}
		$return = preg_replace($pattern, $replace, $string);
		if ($encoding && $encoding !== 'UTF-8') {
			$return = mb_convert_encoding($return, $encoding, 'UTF-8');
		}
		return $return;
	}

	/**
	 * SetEncoding method
	 *
	 * @param Model $Model
	 * @param mixed $encoding null
	 * @param mixed $string
	 * @param mixed $reset null
	 * @return void
	 */
	protected function _setEncoding(Model $Model, &$encoding = null, &$string, $reset = null) {
		if (function_exists('mb_internal_encoding')) {
			$aEncoding = Configure::read('App.encoding');
			if ($aEncoding) {
				if (!$encoding) {
					$encoding = $aEncoding;
				} elseif ($encoding !== $aEncoding) {
					$string = mb_convert_encoding($string, $encoding, $aEncoding);
				}
			} else {
				$encoding = $aEncoding;
			}
			if ($encoding) {
				mb_internal_encoding($encoding);
			}
		}
	}

	/**
	 * Regex method
	 *
	 * Based upon the mode return a partial regex to generate a valid string for the intended use. Note that you
	 * can use almost litterally anything in a url - the limitation is only in what your own application
	 * understands. See the test case for info on how these regex patterns were generated.
	 *
	 * @param string $mode
	 * @return string a partial regex or false on failure
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
		return false;
	}

}
