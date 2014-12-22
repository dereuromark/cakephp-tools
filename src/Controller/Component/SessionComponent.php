<?php
namespace Tools\Controller\Component;

use Cake\Controller\Component;

/**
 * The CakePHP SessionComponent provides a way to persist client data between
 * page requests. It acts as a wrapper for the `$_SESSION` as well as providing
 * convenience methods for several `$_SESSION` related functions.
 *
 * This class is here for backwards compatibility with CakePHP 2.x
 */
class SessionComponent extends Component {

	/**
	 * The Session object instance
	 *
	 * @var \Cake\Network\Session
	 */
	protected $_session;

	/**
	 * Initialize properties.
	 *
	 * @param array $config The config data.
	 * @return void
	 */
	public function initialize(array $config) {
		$this->_session = $this->_registry->getController()->request->session();
	}

	/**
	 * Used to write a value to a session key.
	 *
	 * In your controller: $this->Session->write('Controller.sessKey', 'session value');
	 *
	 * @param string $name The name of the key your are setting in the session.
	 *    This should be in a Controller.key format for better organizing
	 * @param string $value The value you want to store in a session.
	 * @return void
	 */
	public function write($name, $value = null) {
		$this->_session->write($name, $value);
	}

	/**
	 * Used to read a session values for a key or return values for all keys.
	 *
	 * In your controller: $this->Session->read('Controller.sessKey');
	 * Calling the method without a param will return all session vars
	 *
	 * @param string $name the name of the session key you want to read
	 * @return mixed value from the session vars
	 */
	public function read($name = null) {
		return $this->_session->read($name);
	}

	/**
	 * Used to read and delete a session values for a key.
	 *
	 * In your controller: $this->Session->consume('Controller.sessKey');
	 *
	 * @param string $name the name of the session key you want to read
	 * @return mixed value from the session vars
	 */
	public function consume($name) {
		return $this->_session->consume($name);
	}

	/**
	 * Wrapper for SessionComponent::del();
	 *
	 * In your controller: $this->Session->delete('Controller.sessKey');
	 *
	 * @param string $name the name of the session key you want to delete
	 * @return void
	 */
	public function delete($name) {
		$this->_session->delete($name);
	}

	/**
	 * Used to check if a session variable is set
	 *
	 * In your controller: $this->Session->check('Controller.sessKey');
	 *
	 * @param string $name the name of the session key you want to check
	 * @return bool true is session variable is set, false if not
	 */
	public function check($name) {
		return $this->_session->check($name);
	}

	/**
	 * Used to renew a session id
	 *
	 * In your controller: $this->Session->renew();
	 *
	 * @return void
	 */
	public function renew() {
		$this->_session->renew();
	}

	/**
	 * Used to destroy sessions
	 *
	 * In your controller: $this->Session->destroy();
	 *
	 * @return void
	 */
	public function destroy() {
		$this->_session->destroy();
	}

	/**
	 * Get/Set the session id.
	 *
	 * When fetching the session id, the session will be started
	 * if it has not already been started. When setting the session id,
	 * the session will not be started.
	 *
	 * @param string $id Id to use (optional)
	 * @return string The current session id.
	 */
	public function id($id = null) {
		if ($id === null) {
			$session = $this->_session;
			$session->start();
			return $session->id();
		}
		$this->_session->id($id);
	}

	/**
	 * Returns a bool, whether or not the session has been started.
	 *
	 * @return bool
	 */
	public function started() {
		return $this->_session->started();
	}

	/**
	 * Events supported by this component.
	 *
	 * @return array
	 */
	public function implementedEvents() {
		return [];
	}

}
