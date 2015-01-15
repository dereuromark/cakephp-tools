<?php
namespace Tools\Controller;

use Cake\Controller\Controller as CakeController;
use Cake\Core\Configure;
use Cake\Event\Event;

/**
 * DRY Controller stuff
 */
class Controller extends CakeController {

	/**
	 * Add headers for IE8 etc to fix caching issues in those stupid browsers.
	 *
	 * @return void
	 */
	public function disableCache() {
		$this->response->header([
			'Pragma' => 'no-cache',
		]);
		$this->response->disableCache();
	}

	/**
	 * Handles automatic pagination of model records.
	 *
	 * @overwrite to support defaults like limit etc.
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

	/**
	 * Hook to monitor headers being sent.
	 *
	 * This, if desired, adds a check if your controller actions are cleanly built and no headers
	 * or output is being sent prior to the response class, which should be the only one doing this.
	 *
	 * @param Event $event An Event instance
	 * @return void
	 */
	public function afterFilter(Event $event) {
		if (Configure::read('App.monitorHeaders') && $this->name !== 'Error') {
			if (headers_sent($filename, $linenum)) {
				$message = sprintf('Headers already sent in %s on line %s', $filename, $linenum);
				if (Configure::read('debug')) {
					throw new \Exception($message);
				}
				trigger_error($message);
			}
		}
	}

}
