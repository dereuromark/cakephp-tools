<?php
namespace Tools\Controller;

use Cake\Controller\Controller as CakeController;

/**
 * DRY Controller stuff
 */
class Controller extends CakeController {

	/**
	 * Add headers for IE8 etc to fix caching issues in those stupid browsers
	 *
	 * @return void
	 */
	public function disableCache() {
		$this->response->header(array(
			'Pragma' => 'no-cache',
		));
		$this->response->disableCache();
	}

	/**
	 * Handles automatic pagination of model records.
	 *
	 * @overwrite to support defaults like limit, querystring settings
	 * @param \Cake\ORM\Table|string|\Cake\ORM\Query $object Table to paginate
	 *   (e.g: Table instance, 'TableName' or a Query object)
	 * @return \Cake\ORM\ResultSet Query results
	 */
	public function paginate($object = null) {
		if ($defaultSettings = (array)Configure::read('Paginator')) {
			$this->paginate += $defaultSettings;
		}
		return parent::paginate($object);
	}

}
