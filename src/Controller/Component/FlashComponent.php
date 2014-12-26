<?php
namespace Tools\Controller\Component;

use Cake\Controller\Component;
use Cake\Core\Configure;
use Cake\Event\Event;

/**
 * A flash component to enhance flash message support with stackable messages, both
 * persistent and transient.
 *
 * @author Mark Scherer
 * @copyright 2012 Mark Scherer
 * @license MIT
 */
class FlashComponent extends Component {

	public function beforeFilter(Event $event) {
		$this->Controller = $event->subject();
	}

	/**
	 * Called after the Controller::beforeRender(), after the view class is loaded, and before the
	 * Controller::render()
	 *
	 * @param object $Controller Controller with components to beforeRender
	 * @return void
	 */
	public function beforeRender(Event $event) {
		if ($messages = $this->Controller->request->session()->read('Message')) {
			foreach ($messages as $message) {
				$this->flashMessage($message['message'], 'error');
			}
			$this->Controller->request->session()->delete('Message');
		}

		if ($this->Controller->request->is('ajax')) {
			$ajaxMessages = array_merge(
				(array)$this->Controller->request->session()->read('messages'),
				(array)Configure::read('messages')
			);
			// The header can be read with JavaScript and a custom Message can be displayed
			$this->Controller->response->header('X-Ajax-Flashmessage', json_encode($ajaxMessages));

			$this->Controller->request->session()->delete('messages');
		}
	}

	/**
	 * Adds a flash message.
	 * Updates "messages" session content (to enable multiple messages of one type).
	 *
	 * @param string $message Message to output.
	 * @param string $type Type ('error', 'warning', 'success', 'info' or custom class).
	 * @return void
	 */
	public function message($message, $type = null) {
		if (!$type) {
			$type = 'info';
		}

		$old = (array)$this->Controller->request->session()->read('messages');
		if (isset($old[$type]) && count($old[$type]) > 99) {
			array_shift($old[$type]);
		}
		$old[$type][] = $message;
		$this->Controller->request->session()->write('messages', $old);
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
		$defaults = ['element' => 'default'];
		$config += $defaults;
		$this->message($message, $config['element']);
	}

	/**
	 * Adds a transient flash message.
	 * These flash messages that are not saved (only available for current view),
	 * will be merged into the session flash ones prior to output.
	 *
	 * @param string $message Message to output.
	 * @param string $type Type ('error', 'warning', 'success', 'info' or custom class).
	 * @return void
	 */
	public static function transientMessage($message, $type = null) {
		if (!$type) {
			$type = 'info';
		}

		$old = (array)Configure::read('messages');
		if (isset($old[$type]) && count($old[$type]) > 99) {
			array_shift($old[$type]);
		}
		$old[$type][] = $message;
		Configure::write('messages', $old);
	}

}
