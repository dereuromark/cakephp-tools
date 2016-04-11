<?php

namespace Tools\Controller\Component;

use Cake\Core\Configure;
use Cake\Event\Event;
use Cake\Network\Exception\InternalErrorException;
use Cake\Utility\Inflector;
use Shim\Controller\Component\Component;

/**
 * A flash component to enhance flash message support with stackable messages, both
 * persistent and transient.
 *
 * @author Mark Scherer
 * @copyright 2014 Mark Scherer
 * @license MIT
 *
 * @method void success(string $message, array $options = []) Set a message using "success" element
 * @method void error(string $message, array $options = []) Set a message using "error" element
 * @method void warning(string $message, array $options = []) Set a message using "warning" element
 * @method void info(string $message, array $options = []) Set a message using "info" element
 */
class FlashComponent extends Component {

	/**
	 * @var array
	 */
	protected $_defaultConfig = [
		'headerKey' => 'X-Flash', // Set to empty string to deactivate AJAX response
		'sessionLimit' => 99 // Max message limit for session (Configure doesn't need one)
	];

	/**
	 * Called after the Controller::beforeRender(), after the view class is loaded, and before the
	 * Controller::render()
	 *
	 * @param \Cake\Event\Event $event
	 * @return \Cake\Network\Response|null|void
	 */
	public function beforeRender(Event $event) {
		if (!$this->Controller->request->is('ajax')) {
			return;
		}

		$headerKey = $this->config('headerKey');
		if (!$headerKey) {
			return;
		}

		$ajaxMessages = array_merge(
			(array)$this->Controller->request->session()->consume('FlashMessage'),
			(array)Configure::consume('FlashMessage')
		);

		// The header can be read with JavaScript and a custom Message can be displayed
		$this->Controller->response->header($headerKey, json_encode($ajaxMessages));
	}

	/**
	 * Adds a flash message.
	 * Updates "messages" session content (to enable multiple messages of one type).
	 *
	 * @param string $message Message to output.
	 * @param string|null $options Options
	 * @return void
	 */
	public function message($message, $options = null) {
		if (!is_array($options)) {
			$type = $options;
			if (!$type) {
				$type = 'info';
			}
			$options = [];
		} else {
			$options += ['element' => 'info'];
			$type = $options['element'];
		}

		$old = (array)$this->Controller->request->session()->read('FlashMessage');
		if (isset($old[$type]) && count($old[$type]) > $this->config('sessionLimit')) {
			array_shift($old[$type]);
		}
		$old[$type][] = $message;
		$this->Controller->request->session()->write('FlashMessage', $old);
	}

	/**
	 * Wrapper for original core functionality going into this extended component.
	 * Core Auth component, for example, requires this.
	 *
	 * @param string $message
	 * @param array $config
	 * @return void
	 */
	public function set($message, array $config = []) {
		// For now we only use the element name
		$defaults = ['element' => 'info'];
		$config += $defaults;
		$this->message($message, $config['element']);
	}

	/**
	 * Adds a transient flash message.
	 * These flash messages that are not saved (only available for current view),
	 * will be merged into the session flash ones prior to output.
	 *
	 * @param string $message Message to output.
	 * @param string|null $type Type ('error', 'warning', 'success', 'info' or custom class).
	 * @return void
	 */
	public static function transientMessage($message, $type = null) {
		if (!$type) {
			$type = 'info';
		}

		$old = (array)Configure::read('FlashMessage');
		if (isset($old[$type]) && count($old[$type]) > 99) {
			array_shift($old[$type]);
		}
		$old[$type][] = $message;
		Configure::write('FlashMessage', $old);
	}

	/**
	 * Magic method for verbose flash methods based on element names.
	 *
	 * For example: $this->Flash->success('My message') would use the
	 * success.ctp element under `App/Template/Element/Flash` for rendering the
	 * flash message.
	 *
	 * @param string $name Element name to use.
	 * @param array $args Parameters to pass when calling `FlashComponent::message()` or `set()`.
	 * @return void
	 * @throws \Cake\Network\Exception\InternalErrorException If missing the flash message.
	 */
	public function __call($name, $args) {
		$options = ['element' => Inflector::underscore($name)];

		if (count($args) < 1) {
			throw new InternalErrorException('Flash message missing.');
		}

		if (!empty($args[1])) {
			$options += (array)$args[1];
		}
		$this->message($args[0], $options);
	}

}
