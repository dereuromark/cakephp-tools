<?php
App::uses('ModelBehavior', 'Model');
/**
 * ConfirmableBehavior allows forms to easily require a checkbox toggled (confirmed)
 * example: terms of use on registration
 *
 * Copyright 2011, dereuromark (http://www.dereuromark.de)
 *
 * @link          http://github.com/dereuromark/
 * @license       http://www.opensource.org/licenses/mit-license.php The MIT License
 */

/**
 * 2011-07-05 ms
 */
class ConfirmableBehavior extends ModelBehavior {

	protected $_defaults = array(
		'message' => 'Please confirm the checkbox',
		'field' => 'confirm',
		'model' => null,
		'before' => 'validate',
	);

	public $settings = array();

	public function setup(Model $Model, $settings = array()) {
		if (!isset($this->settings[$Model->alias])) {
			$this->settings[$Model->alias] = $this->_defaults;
		}

		$this->settings[$Model->alias] = array_merge($this->settings[$Model->alias], is_array($settings) ? $settings : array());
	}


	public function beforeValidate(Model $Model) {
		$return = parent::beforeValidate($Model);

		if ($this->settings[$Model->alias]['before'] == 'validate') {
			# we dont want to return the value, because other fields might then not be validated
			# (save will not continue with errors, anyway)
			$this->confirm($Model, $return);
		}

		return $return;
	}

	public function beforeSave(Model $Model) {
		$return = parent::beforeSave($Model);

		if ($this->settings[$Model->alias]['before'] == 'save') {
			return $this->confirm($Model, $return);
		}

		return $return;
	}


	/**
	 * Run before a model is saved, used...
	 *
	 * @param object $Model Model about to be saved.
	 * @return boolean true if save should proceed, false otherwise
	 * @access public
	 */
	public function confirm(Model $Model, $return = true) {
		$field = $this->settings[$Model->alias]['field'];
		$message = $this->settings[$Model->alias]['message'];

		if (empty($Model->data[$Model->alias][$field])) {
				$Model->invalidate($field, $message);
				return false;
		}

		return $return;
	}


}

