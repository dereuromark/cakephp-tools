<?php
App::uses('Component', 'Controller');
App::uses('AuthUserTrait', 'Tools.Controller/Component/Auth');

/**
 * Authentication User component class
 */
class AuthUserComponent extends Component {

	use AuthUserTrait;

	public $components = ['Auth'];

	/**
	 * AuthUserComponent::beforeRender()
	 *
	 * @param Controller $controller
	 * @return void
	 */
	public function beforeRender(Controller $controller) {
		$authUser = $this->_getUser();
		$controller->set(compact('authUser'));
	}

	/**
	 * AuthUserComponent::_getUser()
	 *
	 * @return array
	 */
	protected function _getUser() {
		return (array)$this->Auth->user();
	}

}
