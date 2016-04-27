<?php
App::uses('ToolsAppController', 'Tools.Controller');

/**
 * Qurls Controller
 *
 */
class QurlsController extends ToolsAppController {

	public $paginate = [];

	public $components = ['Tools.Common'];

	public function beforeFilter() {
		parent::beforeFilter();

		if (isset($this->Auth)) {
			$this->Auth->allow('go');
		}
	}

	/**
	 * Main login function
	 */
	public function go($key) {
		$entry = $this->Qurl->translate($key);
		if (empty($entry)) {
			throw new NotFoundException();
		}
		//die(returns($entry));
		$note = $entry['Qurl']['note'];
		$url = $entry['Qurl']['url'];

		if ($note) {
			$this->Flash->info(nl2br($note));
		}
		$this->Qurl->markAsUsed($entry['Qurl']['id']);
		return $this->redirect($url);
	}

	/**
	 * @return void
	 */
	public function admin_index() {
		$qurls = $this->paginate();
		$this->set(compact('qurls'));
	}

	/**
	 * @return void
	 */
	public function admin_view($id = null) {
		if (empty($id) || !($qurl = $this->Qurl->find('first', ['conditions' => ['Qurl.id' => $id]]))) {
			$this->Flash->error(__('invalidRecord'));
			return $this->Common->autoRedirect(['action' => 'index']);
		}
		$this->set(compact('qurl'));
	}

	/**
	 * @return void
	 */
	public function admin_add($templateId = null) {
		if ($this->Common->isPosted()) {
			$this->Qurl->create();
			$this->request->data['Qurl']['key'] = '';
			if ($res = $this->Qurl->save($this->request->data)) {
				$var = $this->Qurl->urlByKey($res['Qurl']['key'], $res['Qurl']['title']);

				$this->Flash->success(__('Qurl: %s', $var));
				return $this->Common->postRedirect(['action' => 'index']);
			} else {
				$this->Flash->error(__('formContainsErrors'));
			}
		} else {
			$this->request->data['Qurl']['active'] = 1;

			if ($templateId && ($template = $this->Qurl->get($templateId))) {
				$this->request->data = $template;
			}
		}
	}

	/**
	 * @return void
	 */
	public function admin_edit($id = null) {
		if (empty($id) || !($qurl = $this->Qurl->find('first', ['conditions' => ['Qurl.id' => $id]]))) {
			$this->Flash->error(__('invalidRecord'));
			return $this->Common->autoRedirect(['action' => 'index']);
		}
		if ($this->Common->isPosted()) {
			if ($this->Qurl->save($this->request->data)) {
				$var = $this->request->data['Qurl']['key'];
				$this->Flash->success(__('record edit %s saved', h($var)));
				return $this->Common->postRedirect(['action' => 'index']);
			} else {
				$this->Flash->error(__('formContainsErrors'));
			}
		}
		if (empty($this->request->data)) {
			$this->request->data = $qurl;
		}
	}

	/**
	 * @return void
	 */
	public function admin_delete($id = null) {
		$this->request->allowMethod('post');
		if (empty($id) || !($qurl = $this->Qurl->find('first', ['conditions' => ['Qurl.id' => $id], 'fields' => ['id', 'key']]))) {
			$this->Flash->error(__('invalidRecord'));
			return $this->Common->autoRedirect(['action' => 'index']);
		}
		$var = $qurl['Qurl']['key'];

		if ($this->Qurl->delete($id)) {
			$this->Flash->success(__('record del %s done', h($var)));
			return $this->redirect(['action' => 'index']);
		}
		$this->Flash->error(__('record del %s not done exception', h($var)));
		return $this->Common->autoRedirect(['action' => 'index']);
	}

}
