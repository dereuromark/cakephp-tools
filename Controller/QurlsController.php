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
			$this->Flash->message(nl2br($note), 'info');
		}
		$this->Qurl->markAsUsed($entry['Qurl']['id']);
		return $this->redirect($url);
	}

	/**
	 * @return void
	 */
	public function admin_index() {
		$this->Qurl->recursive = 0;
		$qurls = $this->paginate();
		$this->set(compact('qurls'));
	}

	/**
	 * @return void
	 */
	public function admin_view($id = null) {
		$this->Qurl->recursive = 0;
		if (empty($id) || !($qurl = $this->Qurl->find('first', ['conditions' => ['Qurl.id' => $id]]))) {
			$this->Flash->message(__('invalidRecord'), 'error');
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

				$this->Flash->message(__('Qurl: %s', $var), 'success');
				return $this->Common->postRedirect(['action' => 'index']);
			} else {
				$this->Flash->message(__('formContainsErrors'), 'error');
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
			$this->Flash->message(__('invalidRecord'), 'error');
			return $this->Common->autoRedirect(['action' => 'index']);
		}
		if ($this->Common->isPosted()) {
			if ($this->Qurl->save($this->request->data)) {
				$var = $this->request->data['Qurl']['key'];
				$this->Flash->message(__('record edit %s saved', h($var)), 'success');
				return $this->Common->postRedirect(['action' => 'index']);
			} else {
				$this->Flash->message(__('formContainsErrors'), 'error');
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
			$this->Flash->message(__('invalidRecord'), 'error');
			return $this->Common->autoRedirect(['action' => 'index']);
		}
		$var = $qurl['Qurl']['key'];

		if ($this->Qurl->delete($id)) {
			$this->Flash->message(__('record del %s done', h($var)), 'success');
			return $this->redirect(['action' => 'index']);
		}
		$this->Flash->message(__('record del %s not done exception', h($var)), 'error');
		return $this->Common->autoRedirect(['action' => 'index']);
	}

}
