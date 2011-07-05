<?php
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

	var $__settings = array();


	function setup(&$Model, $settings = array()) {
		$default = array('message' => __('Please confirm the checkbox', true), 'field' => 'confirm', 'model'=>null, 'before'=>'validate');

		if (!isset($this->__settings[$Model->alias])) {
			$this->__settings[$Model->alias] = $default;
		}

		$this->__settings[$Model->alias] = array_merge($this->__settings[$Model->alias], is_array($settings) ? $settings : array());
	}


	function beforeValidate(&$Model) {
		$return = parent::beforeValidate($Model);

		if ($this->__settings[$Model->alias]['before'] == 'validate') {
			# we dont want to return the value, because other fields might then not be validated 
			# (save will not continue with errors, anyway)
			$this->confirm($Model, $return);
		}

		return $return;
	}

	function beforeSave(&$Model) {
		$return = parent::beforeSave($Model);

		if ($this->__settings[$Model->alias]['before'] == 'save') {
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
	function confirm(&$Model, $return = true) {
		$field = $this->__settings[$Model->alias]['field'];
		$message = $this->__settings[$Model->alias]['message'];
		
		if (empty($Model->data[$Model->alias][$field])) {
				$Model->invalidate($field, $message);
				return false;
		}
		
		return $return;
	}


}

