<?php

namespace Tools\View\Helper;

use Cake\View\Helper;
use RuntimeException;
use Tools\Auth\AuthUserTrait;

/**
 * Helper to access auth user data.
 *
 * @author Mark Scherer
 */
class AuthUserHelper extends Helper {

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
