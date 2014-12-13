<?php

namespace Tools\Controller\Component;

use Cake\Controller\Component;
use Cake\Event\Event;
use Tools\Auth\AuthUserTrait;

/**
 * Authentication User component class
 */
class AuthUserComponent extends Component {

	use AuthUserTrait;

	public $components = ['Auth', 'Tools.Session'];

	/**
	 * AuthUserComponent::beforeRender()
	 *
	 * @param Event $event
	 * @return void
	 */
	public function beforeRender(Event $event) {
		$controller = $event->subject();
		$authUser = $this->_getUser();
		$controller->set(compact('authUser'));
	}

	protected function _getUser() {
		return (array)$this->Auth->user();
	}

}
