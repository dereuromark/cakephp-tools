<?php
/**
 * PHP 5
 *
 * @author Mark Scherer
 * @cakephp 2.x
 * @license MIT
 */

App::uses('ModelBehavior', 'Model');

/**
 * Replace regionalized chars with standard ones on input.
 *
 * “smart quotes” become "dumb quotes" on save
 * „low-high“ become "high-high"
 * same for single quotes (apostrophes)
 * in order to unify them. Basic idea is a unified non-regional version in the database.
 *
 * Using the TypographyHelper we can then format the output
 * according to the language/regional setting (in some languages
 * the high-high smart quotes, in others the low-high ones are preferred)
 *
 * Settings are:
 * - string $before (validate/save)
 * - array $fields (leave empty for auto detection)
 * - bool $mergeQuotes (merge single and double into " or any custom char)
 *
 * TODOS:
 * - respect primary and secondary quotations marks as well as alternatives
 *
 * @link http://www.dereuromark.de/2012/08/12/typographic-behavior-and-typography-helper/
 * @link http://en.wikipedia.org/wiki/Non-English_usage_of_quotation_marks
 */
class TypographicBehavior extends ModelBehavior {

	protected $_map = array(
		'in' => array(
			'‘' => '\'',
			// Translates to '&lsquo;'.
			'’' => '\'',
			// Translates to '&rsquo;'.
			'‚' => '\'',
			// Translates to '&sbquo;'.
			'‛' => '\'',
			// Translates to '&#8219;'.
			'“' => '"',
			// Translates to '&ldquo;'.
			'”' => '"',
			// Translates to '&rdquo;'.
			'„' => '"',
			// Translates to '&bdquo;'.
			'‟' => '"',
			// Translates to '&#8223;'.
			'«' => '"',
			// Translates to '&laquo;'.
			'»' => '"',
			// Translates to '&raquo;'.
			'‹' => '\'',
			// Translates to '&laquo;'.
			'›' => '\'',
			// Translates to '&raquo;'.
		),
		'out' => array(
			// Use the TypographyHelper for this at runtime.
		),
	);

	protected $_defaults = array(
		'before' => 'save',
		'fields' => array(),
		'mergeQuotes' => false, // Set to true for " or explicitly set a char (" or ').
	);

	/**
	 * Initiate behavior for the model using specified settings.
	 * Available settings:
	 *
	 * @param object $Model Model using the behaviour
	 * @param array $settings Settings to override for model.
	 * @return void
	 */
	public function setup(Model $Model, $settings = array()) {
		if (!isset($this->settings[$Model->alias])) {
			$this->settings[$Model->alias] = $this->_defaults;
		}
		$this->settings[$Model->alias] = array_merge($this->settings[$Model->alias], $settings);

		if (empty($this->settings[$Model->alias]['fields'])) {
			$schema = $Model->schema();
			$fields = array();
			foreach ($schema as $field => $v) {
				if (!in_array($v['type'], array('string', 'text'))) {
					continue;
				}
				if (!empty($v['key'])) {
					continue;
				}
				if (isset($v['length']) && $v['length'] === 1) { // TODO: also skip UUID (lenght 36)?
					continue;
				}
				$fields[] = $field;
			}
			$this->settings[$Model->alias]['fields'] = $fields;
		}
		if ($this->settings[$Model->alias]['mergeQuotes'] === true) {
			$this->settings[$Model->alias]['mergeQuotes'] = '"';
		}
	}

	/**
	 * TypographicBehavior::beforeValidate()
	 *
	 * @param Model $Model
	 * @return boolean Success
	 */
	public function beforeValidate(Model $Model, $options = array()) {
		parent::beforeValidate($Model, $options);

		if ($this->settings[$Model->alias]['before'] === 'validate') {
			$this->process($Model);
		}

		return true;
	}

	/**
	 * TypographicBehavior::beforeSave()
	 *
	 * @param Model $Model
	 * @return boolean Success
	 */
	public function beforeSave(Model $Model, $options = array()) {
		parent::beforeSave($Model, $options);

		if ($this->settings[$Model->alias]['before'] === 'save') {
			$this->process($Model);
		}

		return true;
	}

	/**
	 * Run the behavior over all records of this model
	 * This is useful if you attach it after some records have already been saved without it.
	 *
	 * @param Model $Model The model about to be saved.
	 * @return integer count Number of affected/changed records
	 */
	public function updateTypography(Model $Model, $dryRun = false) {
		$options = array('recursive' => -1, 'limit' => 100, 'offset' => 0);
		$count = 0;
		while ($records = $Model->find('all', $options)) {
			foreach ($records as $record) {
				$changed = false;
				foreach ($this->settings[$Model->alias]['fields'] as $field) {
					if (empty($record[$Model->alias][$field])) {
						continue;
					}
					$tmp = $this->_prepareInput($Model, $record[$Model->alias][$field]);
					if ($tmp == $record[$Model->alias][$field]) {
						continue;
					}
					$record[$Model->alias][$field] = $tmp;
					$changed = true;
				}
				if ($changed) {
					if (!$dryRun) {
						$Model->save($record, false);
					}
					$count++;
				}
			}
			$options['offset'] += 100;
		}
		return $count;
	}

	/**
	 * Run before a model is saved
	 *
	 * @param object $Model Model about to be saved.
	 * @return boolean true if save should proceed, false otherwise
	 */
	public function process(Model $Model, $return = true) {
		foreach ($this->settings[$Model->alias]['fields'] as $field) {
			if (!empty($Model->data[$Model->alias][$field])) {
				$Model->data[$Model->alias][$field] = $this->_prepareInput($Model, $Model->data[$Model->alias][$field]);
			}
		}

		return $return;
	}

	/**
	 * @param string $input
	 * @return string cleanedInput
	 */
	protected function _prepareInput(Model $Model, $string) {
		$map = $this->_map['in'];
		if ($this->settings[$Model->alias]['mergeQuotes']) {
			foreach ($map as $key => $val) {
				$map[$key] = $this->settings[$Model->alias]['mergeQuotes'];
			}
		}
		return str_replace(array_keys($map), array_values($map), $string);
	}

}
