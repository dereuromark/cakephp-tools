<?php
App::uses('ModelBehavior', 'Model');
App::uses('HazardLib', 'Tools.Lib');

/**
 * Uses the HazardLib to test well known injection snippets of all kinds (including XSS, SQL)
 * and tests your db wrapper methods or populates your records with it.
 * Use this ONLY for testing environments and never on your live data.
 *
 * Available snippet types are XSS, PHP and SQL.
 *
 * The main concern is not just "eval" users that might try to hijack/abuse your site.
 * Not properly securing your view also means that strings like `some>cool<string` will most likely mess up your HTML.
 * The view could be rendered as a complete mess without the user or the developer knowing it. It might have even been
 * the admin which inserted those layout-breaking strings, after all.
 * That's why it is so important to follow the rule "do NOT sanitize, validate input, escape output" (there are exceptions, of course).
 * Also make sure, you already cover those basics in your baking template. This will save a lot of time in the long run.
 *
 * If you inserted records go and browse your backend and especially your frontend.
 * Everywhere where you get some alert or strange behavior, you might have forgotten to use h() or other
 * measures to secure your output properly.
 *
 * You can also apply this behavior globally to overwrite all strings in your application temporarily.
 * This way you don't need to modify the database. On output it will just inject the hazardous strings and
 * you can browse your website just as if they were actually stored in your db.
 *
 * Add it to some models or even the AppModel (temporarily!) as `$actsAs = array('Tools.Hazardable'))`.
 * A known limitation of Cake behaviors is, though, that this would only apply for first-level records (not related data).
 * So it is usually better to insert some hazardous strings into all your tables and make your tests then as closely
 * to the reality as possible.
 *
 * @author Mark Scherer
 * @license MIT
 */
class HazardableBehavior extends ModelBehavior {

	protected $_defaults = array(
		'replaceFind' => false, // fake data after a find call (defaults to false)
		'fields' => array(), // additional fields or custom mapping to a specific snippet type (defaults to XSS)
		'skipFields' => array('id', 'slug') // fields of the schema that should be skipped
	);

	protected $_snippets = array();

	/**
	 * HazardableBehavior::setup()
	 *
	 * @param Model $Model
	 * @param array $config
	 * @return void
	 */
	public function setup(Model $Model, $config = array()) {
		$this->settings[$Model->alias] = array_merge($this->_defaults, $config);
	}

	/**
	 * BeforeSave() to inject the hazardous strings into the model data for save().
	 *
	 * Note: Remember to disable validation as you want to insert those strings just for
	 * testing purposes.
	 */
	public function beforeSave(Model $Model, $options = array()) {
		$fields = $this->_fields($Model);
		foreach ($fields as $field) {
			$length = 0;
			$schema = $Model->schema($field);
			if (!empty($schema['length'])) {
				$length = $schema['length'];
			}
			$Model->data[$Model->alias][$field] = $this->_snippet($length);
		}

		return true;
	}

	/**
	 * AfterFind() to inject the hazardous strings into the retrieved model data.
	 * Only activate this if you have not persistently stored any hazardous strings yet.
	 */
	public function afterFind(Model $Model, $results, $primary = false) {
		if (empty($this->settings[$Model->alias]['replaceFind'])) {
			return $results;
		}

		foreach ($results as $key => $result) {
			foreach ($result as $model => $row) {
				$fields = $this->_fields($Model);
				foreach ($fields as $field) {
					$length = 0;
					$schema = $Model->schema($field);
					if (!empty($schema['length'])) {
						$length = $schema['length'];
					}
					$results[$key][$model][$field] = $this->_snippet($length);
				}
			}
		}
		return $results;
	}

	/**
	 * @param integer $maxLength The lenght of the field if applicable to return a suitable snippet
	 * @return string Hazardous string
	 */
	protected function _snippet($maxLength = 0) {
		$snippets = $this->_snippets();
		$max = count($snippets) - 1;

		if ($maxLength) {
			foreach ($snippets as $key => $snippet) {
				if (mb_strlen($snippet) > $maxLength) {
					break;
				}
				$max = $key;
			}
		}

		$keyByChance = mt_rand(0, $max);
		return $snippets[$keyByChance];
	}

	/**
	 * @return array
	 */
	protected function _snippets() {
		if ($this->_snippets) {
			return $this->_snippets;
		}
		$snippetArray = HazardLib::xssStrings();
		$snippetArray[] = '<SCRIPT>alert(\'X\')</SCRIPT>';
		$snippetArray[] = '<';

		usort($snippetArray, array($this, '_sort'));

		$this->_snippets = $snippetArray;
		return $snippetArray;
	}

	/**
	 * Sort all snippets by length (ASC)
	 */
	protected function _sort($a, $b) {
		return strlen($a) - strlen($b);
	}

	/**
	 * @return array
	 */
	protected function _fields(Model $Model) {
		$fields = array();
		$schema = $Model->schema();
		foreach ($schema as $key => $field) {
			if (!in_array($field['type'], array('text', 'string'))) {
				continue;
			}
			if ($this->settings[$Model->alias]['skipFields'] && in_array($key, $this->settings[$Model->alias]['skipFields'])) {
				continue;
			}
			$fields[] = $key;
		}
		return $fields;
	}

}
