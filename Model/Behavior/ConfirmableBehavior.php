<?php
App::uses('ModelBehavior', 'Model');

/**
 * ConfirmableBehavior allows forms to easily require a checkbox toggled (confirmed).
 * Example: Terms of use on registration forms or some "confirm delete checkbox"
 *
 * Copyright 2011, dereuromark (http://www.dereuromark.de)
 *
 * @link http://github.com/dereuromark/
 * @license http://opensource.org/licenses/mit-license.php MIT
 * @link http://www.dereuromark.de/2011/07/05/introducing-two-cakephp-behaviors/
 */
class ConfirmableBehavior extends ModelBehavior {

	protected $_defaultConfig = [
		'message' => 'Please confirm the checkbox',
		'field' => 'confirm',
		'model' => null,
		'before' => 'validate',
	];

	public function setup(Model $Model, $config = []) {
		if (!isset($this->settings[$Model->alias])) {
			$this->settings[$Model->alias] = $this->_defaultConfig;
		}
		$this->settings[$Model->alias] = $config + $this->settings[$Model->alias];
	}

	/**
	 * ConfirmableBehavior::beforeValidate()
	 *
	 * @param Model $Model
	 * @return bool Success
	 */
	public function beforeValidate(Model $Model, $options = []) {
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
	public function beforeSave(Model $Model, $options = []) {
		$return = parent::beforeSave($Model, $options);

		if ($this->settings[$Model->alias]['before'] === 'save') {
			return $this->confirm($Model, $return);
		}

		return $return;
	}

	/**
	 * The actual logic
	 *
	 * @param Model $Model Model about to be saved.
	 * @return bool true if save should proceed, false otherwise
	 */
	public function confirm(Model $Model, $return = true) {
		$field = $this->settings[$Model->alias]['field'];
		$message = $this->settings[$Model->alias]['message'];

		if (empty($Model->data[$Model->alias][$field])) {
			$Model->invalidate($field, __d('tools', $message));
			return false;
		}

		return $return;
	}

}
