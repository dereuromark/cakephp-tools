<?php
if (!defined('CLASS_USER')) {
	define('CLASS_USER', 'User');
}

App::uses('ToolsAppController', 'Tools.Controller');

class QloginController extends ToolsAppController {

	public $uses = ['Tools.Qlogin'];

	public $components = ['Tools.Flash', 'Tools.Common'];

	public function beforeFilter() {
		parent::beforeFilter();

		if (isset($this->Auth)) {
			$this->Auth->allow('go');
		}
	}

	/**
	 * Main login function
	 *
	 * @return void
	 */
	public function go($key = null) {
		if (!$key) {
			throw new NotFoundException();
		}
		$entry = $this->Qlogin->translate($key);
		$default = '/';
		if ($this->Session->read('Auth.User.id') && isset($this->Auth->loginRedirect)) {
			$default = $this->Auth->loginRedirect;
		}

		if (empty($entry)) {
			$this->Flash->message(__d('tools', 'Invalid Key'), 'error');
			return $this->Common->autoRedirect($default);
		}

		$alias = Configure::read('Qlogin.generator') ?: 'Token';
		$uid = $entry[$alias]['user_id'];
		$url = $entry[$alias]['url'];

		if (!$this->Session->read('Auth.User.id')) {
			if ($this->Common->manualLogin($uid)) {
				$this->Session->write('Auth.User.Login.qlogin', true);
				if (!Configure::read('Qlogin.suppressMessage')) {
					$this->Flash->message(__d('tools', 'You successfully logged in via qlogin'), 'success');
				}
			} else {
				$this->Flash->message($this->Auth->loginError, 'error');
				$url = $default;
				trigger_error($this->Auth->loginError . ' - uid ' . $uid);
			}
		}
		return $this->redirect($url);
	}

	/**
	 * These params can be passed to preset the form
	 * - user_id
	 * - url (base64encoded)
	 *
	 * @return void
	 */
	public function admin_index() {
		if ($this->Common->isPosted()) {
			$this->Qlogin->set($this->request->data);
			if ($this->Qlogin->validates()) {
				$id = $this->Qlogin->generate($this->Qlogin->data['Qlogin']['url'], $this->Qlogin->data['Qlogin']['user_id']);
				$this->Flash->message('New Key: ' . h($id), 'success');
				$url = $this->Qlogin->urlByKey($id);
				$this->set(compact('url'));
				$this->request->data = [];
			}
		} else {
			$this->request->data['Qlogin'] = $this->request->query;
		}

		$this->User = ClassRegistry::init(CLASS_USER);
		$users = $this->User->find('list');

		$this->Token = ClassRegistry::init('Tools.Token');
		$qlogins = $this->Token->find('count', ['conditions' => ['type' => 'qlogin']]);

		$this->set(compact('users', 'qlogins'));
	}

	/**
	 * QloginController::admin_listing()
	 *
	 * @return void
	 */
	public function admin_listing() {
	}

	/**
	 * QloginController::admin_reset()
	 *
	 * @return void
	 */
	public function admin_reset() {
		$this->request->allowMethod(['post', 'delete']);
		$this->Token = ClassRegistry::init('Tools.Token');
		$this->Token->deleteAll(['type' => 'qlogin']);
		$this->Flash->message(__d('tools', 'Success'), 'success');
		return $this->Common->autoRedirect(['action' => 'index']);
	}

}
