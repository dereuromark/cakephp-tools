<?php

namespace Tools\Controller;

use Cake\Core\Configure;
use Shim\Controller\Controller as ShimController;

/**
 * @property \Tools\Controller\Component\CommonComponent $Common
 * @property \Tools\Controller\Component\UrlComponent $Url
 */
class Controller extends ShimController {

	/**
	 * Handles automatic pagination of model records.
	 *
	 * @override To support defaults like limit etc.
	 *
	 * @param \Cake\ORM\Table|string|\Cake\ORM\Query|null $object Table to paginate
	 *   (e.g: Table instance, 'TableName' or a Query object)
	 * @param array $settings Settings
	 *
	 * @return \Cake\ORM\ResultSet|\Cake\Datasource\ResultSetInterface Query results
	 */
	public function paginate($object = null, array $settings = []) {
		$defaultSettings = (array)Configure::read('Paginator');
		if ($defaultSettings) {
			$this->paginate += $defaultSettings;
		}
		return parent::paginate($object, $settings);
	}

}
