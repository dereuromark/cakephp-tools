<?php
App::uses('ToolsAppController', 'Tools.Controller');

/**
 * Tiny Url Generation
 *
 * Tip:
 * Apply this route (/Config/routes.php):
 *
 * Router::connect('/s/:id',
 *   array('plugin' => 'tools', 'controller' => 'tiny_urls', 'action' => 'go'),
 *   array('id' => '[0-9a-zA-Z]+'));
 * Result:
 * /domain/s/ID
 */
class TinyUrlsController extends ToolsAppController {

	public $uses = ['Tools.TinyUrl'];

	public function beforeFilter() {
		parent::beforeFilter();

		if (isset($this->Auth)) {
			$this->Auth->allow('go');
		}
	}

	/****************************************************************************************
	* ADMIN functions
	****************************************************************************************/

	/**
	 * Main redirect function
	 *
	 * @return void
	 */
	public function go() {
		$id = $this->request->query('id');
		if (!empty($this->request->params['id'])) {
			$id = $this->request->params['id'];
		}
		if (!$id) {
			throw new NotFoundException();
		}
		$entry = $this->TinyUrl->translate($id);
		if (empty($entry)) {
			throw new NotFoundException();
		}

		$url = $entry['TinyUrl']['target'];

		if (!empty($message)) {
			$type = !empty($entry['TinyUrl']['flash_type']) ? $entry['TinyUrl']['flash_type'] : 'success';
			$this->Flash->message($message, $type);
		}
		$this->TinyUrl->up($entry['TinyUrl']['id'], ['field' => 'used', 'modify' => true, 'timestampField' => 'last_used']);
		return $this->redirect($url, 301);
	}

	/**
	 * TinyUrlsController::admin_index()
	 *
	 * @return void
	 */
	public function admin_index() {
		if ($this->Common->isPosted()) {
			$this->TinyUrl->set($this->request->data);
			if ($this->TinyUrl->validates()) {
				$id = $this->TinyUrl->generate($this->TinyUrl->data['TinyUrl']['url']);
				$this->Flash->message('New Key: ' . h($id), 'success');
				$url = $this->TinyUrl->urlByKey($id);
				$this->set(compact('url'));
				$this->request->data = [];
			}
		}

		$tinyUrls = $this->TinyUrl->find('count', ['conditions' => []]);

		$this->set(compact('tinyUrls'));
	}

	/**
	 * TinyUrlsController::admin_listing()
	 *
	 * @return void
	 */
	public function admin_listing() {
	}

	/**
	 * TinyUrlsController::admin_reset()
	 *
	 * @return void
	 */
	public function admin_reset() {
		$this->request->allowMethod('post');
		$this->TinyUrl->truncate();
		$this->Flash->message(__d('tools', 'Done'), 'success');
		return $this->Common->autoRedirect(['action' => 'index']);
	}

}
