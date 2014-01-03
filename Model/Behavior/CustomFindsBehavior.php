<?php
App::uses('ModelBehavior', 'Model');

/**
 * CustomFinds Behavior class
 *
 * Behavior for CakePHP that enables you to configure custom
 * queries in your Model classes.
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @author Ariel Patschiki, Daniel L. Pakuschewski
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 * @copyright Copyright 2010, MobVox Solu??es Digitais.
 * @version 0.1
 */

/**
 * @version 0.2:
 * @modified Mark Scherer
 * - works with cakephp2.x
 * - added key: remove (to remove some custom fields again)
 * - rewritten method: modifyQuery()
 * - test case added
 */
class CustomFindsBehavior extends ModelBehavior {

	/**
	 * Prevent that Containable is loaded after CustomFinds.
	 * Containable Behavior need to be loaded before CustomFinds Behavior.
	 *
	 * @param Model $Model
	 * @param array $query
	 */
	protected function _verifyContainable(Model $Model, $query) {
		if (is_array($Model->actsAs) && in_array('Containable', $Model->actsAs) && isset($query['contain'])) {
			if (array_search('CustomFinds', $Model->actsAs) > array_search('Containable', $Model->actsAs)) {
				trigger_error(__('The behavior "Containable", if used together with "CustomFinds" needs to be loaded before.'), E_USER_WARNING);
			}
		}
	}

	protected function _modifyQuery(Model $Model, $query) {
		$customQuery = $Model->customFinds[$query['custom']];
		unset($query['custom']);

		if (isset($query['remove'])) {
			$removes = (array)$query['remove'];
			unset($query['remove']);
			$this->_remove($customQuery, $removes);
		}
		return Set::merge($customQuery, $query);
	}

	//TODO: fixme for deeper arrays

	protected function _remove(&$query, $removes) {
		foreach ($removes as $key => $remove) {
			//$query = Set::remove($query, $remove); # doesnt work due to dot syntax
			if (is_string($remove)) {
				if (isset($query[$remove])) {
					unset($query[$remove]);
				}
				return;
			}
			foreach ($remove as $subKey => $subRemove) {
				if (is_string($subKey) && isset($query[$remove][$subKey])) {
					return $this->_remove($query[$remove][$subKey], $subRemove);
				}

				if (is_string($subRemove)) {
					if (isset($query[$key][$subRemove])) {
						unset($query[$key][$subRemove]);
						return;
					}
					/*
					if (is_string($subKey) && isset($subRemove, $query[$key][$subKey])) {
						continue;
					}
					*/
					/*
					if (!isset($query[$remove])) {
						continue;
					}
					*/
					/*
					$element = array_shift(array_keys($query[$key], $subRemove));
					unset($query[$key][$element]);
					return;
					*/
				}
				//return $this->_remove($query[$key], $subRemove);
			}
		}
	}

	/**
	 * Get customFinds at Model and merge with query.
	 *
	 * @param Model $Model
	 * @param array $query
	 * @return array
	 */
	public function beforeFind(Model $Model, $query) {
		if (isset($Model->customFinds) && isset($query['custom']) && isset($Model->customFinds[$query['custom']])) {
			$query = $this->_modifyQuery($Model, $query);
			$this->_verifyContainable($Model, $query);
			return $query;
		}
		return true;
	}

}
