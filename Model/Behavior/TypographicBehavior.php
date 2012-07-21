<?php
App::uses('ModelBehavior', 'Model');

/**
 * “smart quotes” become "dumb quotes" on save
 * „low-high“ become "high-high"
 * same for single quotes (apostrophes)
 * in order to unify them
 *
 * using the TypographyHelper we can then format the output
 * according to the language/regional setting (in some languages
 * the high-high smart quotes, in others the low-high ones are preferred)
 *
 * @link http://en.wikipedia.org/wiki/Non-English_usage_of_quotation_marks
 * @cakephp 2.0
 * 2011-01-13 ms
 */
class TypographicBehavior extends ModelBehavior {

	protected $map = array(
		'in' => array(
			'‘' => '"',
			//'&lsquo;' => '"', # ‘
			'’' => '"',
			//'&rsquo;' => '"', # ’
			'‚' => '"',
			//'&sbquo;' => '"', # ‚
			'‛' => '"',
			//'&#8219;' => '"', # ‛
			'“' => '"',
			//'&ldquo;' => '"', # “
			'”' => '"',
			//'&rdquo;' => '"', # ”
			'„' => '"',
			//'&bdquo;' => '"', # „
			'‟' => '"',
			//'&#8223;' => '"', # ‟
			'«' => '"',
			//'&laquo;' => '"', # «
			'»' => '"',
			//'&raquo;' => '"', # »
			'‹' => '"',
			//'&laquo;' => '"', # ‹
			'›' => '"',
			//'&raquo;' => '"', # ›
		),
		'out'=> array(
			# use the TypographyHelper for this at runtime
		),
	);

	/**
	 * Initiate behavior for the model using specified settings. Available settings:
	 *
	 *
	 * @param object $Model Model using the behaviour
	 * @param array $settings Settings to override for model.
	 * @access public
	 * 2011-12-06 ms
	 */
	public function setup(Model $Model, $settings = array()) {
		$default = array(
			'before' => 'save',
			'fields' => array()
		);
		if (!isset($this->settings[$Model->alias])) {
			$this->settings[$Model->alias] = $default;
		}

		$this->settings[$Model->alias] = array_merge($this->settings[$Model->alias], is_array($settings) ? $settings : array());
	}


	public function beforeValidate(Model $Model) {
		parent::beforeValidate($Model);

		if ($this->settings[$Model->alias]['before'] == 'validate') {
			$this->process($Model);
		}

		return true;
	}

	public function beforeSave(Model $Model) {
		parent::beforeSave($Model);

		if ($this->settings[$Model->alias]['before'] == 'save') {
			$this->process($Model);
		}

		return true;
	}


	/**
	 * Run before a model is saved
	 *
	 * @param object $Model Model about to be saved.
	 * @return boolean true if save should proceed, false otherwise
	 * @access public
	 */
	public function process(Model $Model, $return = true) {
		foreach ($this->settings[$Model->alias]['fields'] as $field) {
			if (!empty($Model->data[$Model->alias][$field])) {
				$Model->data[$Model->alias][$field] = $this->_prepareInput($Model->data[$Model->alias][$field]);
			}
		}

		return $return;
	}

	/**
	 * @param string $input
	 * @return string $cleanedInput
	 * 2011-12-06 ms
	 */
	protected function _prepareInput($string) {
		$map = $this->map['in'];

		//return $string;

		$string = str_replace(array_keys($map), array_values($map), $string);
		return $string;
	}

}
