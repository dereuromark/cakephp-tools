<?php
App::uses('ModelBehavior', 'Model');
/**
 * ConfirmableBehavior allows forms to easily require a checkbox toggled (confirmed).
 * Example: Terms of use on registration forms or some "confirm delete checkbox"
 *
 * Copyright 2011, dereuromark (http://www.dereuromark.de)
 *
 * @link http://github.com/dereuromark/
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 * @link http://www.dereuromark.de/2011/07/05/introducing-two-cakephp-behaviors/
 */
class ConfirmableBehavior extends ModelBehavior {

	protected $_defaults = array(
		'message' => 'Please confirm the checkbox',
		'field' => 'confirm',
		'model' => null,
		'before' => 'validate',
	);

	public function setup(Model $Model, $settings = array()) {
		if (!isset($this->settings[$Model->alias])) {
			$this->settings[$Model->alias] = $this->_defaults;
		}

		$this->settings[$Model->alias] = array_merge($this->settings[$Model->alias], is_array($settings) ? $settings : array());
	}

	/**
	 * ConfirmableBehavior::beforeValidate()
	 *
	 * @param Model $Model
	 * @return boolean Success
	 */
	public function beforeValidate(Model $Model, $options = array()) {
		$return = parent::beforeValidate($Model, $options);

		if ($this->settings[$Model->alias]['before'] === 'validate') {
			// we dont want to return the value, because other fields might then not be validated
			// (save will not continue with errors, anyway)
			$this->confirm($Model, $return);
		}

		return $return;
	}

	/**
	 * ConfirmableBehavior::beforeSave()
	 *
	 * @param Model $Model
	 * @return mixed
	 */
	public function beforeSave(Model $Model, $options = array()) {
		$return = parent::beforeSave($Model, $options);

		if ($this->settings[$Model->alias]['before'] === 'save') {
			return $this->confirm($Model, $return);
		}

		return $return;
	}

	/**
	 * The actual logic
	 *
	 * @param object $Model Model about to be saved.
	 * @return boolean true if save should proceed, false otherwise
	 */
	public function confirm(Model $Model, $return = true) {
		$field = $this->settings[$Model->alias]['field'];
		$message = $this->settings[$Model->alias]['message'];

		if (empty($Model->data[$Model->alias][$field])) {
				$Model->invalidate($field, __($message));
				return false;
		}

		return $return;
	}

}
