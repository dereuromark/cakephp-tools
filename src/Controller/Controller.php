<?php

namespace Tools\Controller;

use Cake\Core\Configure;
use Shim\Controller\Controller as ShimController;

/**
 * DRY Controller stuff
 */
class Controller extends ShimController {

	/**
	 * Handles automatic pagination of model records.
	 *
	 * @override To support defaults like limit etc.
	 *
	 * @param \Cake\ORM\Table|string|\Cake\ORM\Query|null $object Table to paginate
	 *   (e.g: Table instance, 'TableName' or a Query object)
	 * @return \Cake\ORM\ResultSet Query results
	 */
	public function paginate($object = null) {
		$defaultSettings = (array)Configure::read('Paginator');
		if ($defaultSettings) {
			$this->paginate += $defaultSettings;
		}
		return parent::paginate($object);
	}

}
