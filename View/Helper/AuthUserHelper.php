<?php
App::uses('AppHelper', 'View/Helper');
App::uses('AuthUserTrait', 'Tools.Controller/Component/Auth');

/**
 * Helper to access auth user data.
 *
 * @author Mark Scherer
 */
class AuthUserHelper extends AppHelper {

	use AuthUserTrait;

	/**
	 * AuthUserHelper::_getUser()
	 *
	 * @return array
	 */
	protected function _getUser() {
		if (!isset($this->_View->viewVars['authUser'])) {
			throw new RuntimeException('AuthUser helper needs AuthUser component to function');
		}
		return $this->_View->viewVars['authUser'];
	}

}
