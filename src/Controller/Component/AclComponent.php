<?php

namespace Tools\Controller\Component;

use Shim\Controller\Component\Component;
use Cake\Controller\ComponentRegistry;
use Cake\Network\Request;
use Cake\Auth;

/**
 * A class for checking access permissions.
 *
 * @author Bernhard Picher
 * @license MIT
 */
class AclComponent extends Component {

	public $components = ['Auth'];

	/**
	 * AclComponent:check ( $controller, $action, $plugin )
	 * 
	 * Checks if the currently logged in user would have access to the given request.
	 *
	 * @param string $controller Controller name.
	 * @param string $action Action name.
	 * @param string $plugin Plugin name. Defaults to `null`.
     * @return bool True if access would be granted
	 */
	public function check ( $controller, $action, $plugin = 'null' ) {
		$request = new Request();
		$request->addParams( [ 'controller' => $controller, 'action' => $action ] );
		
		return $this->Auth->isAuthorized( null, $request );
	}
}