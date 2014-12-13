<?php

App::uses('Component', 'Controller');

/**
 * A flash component to enhance flash message support with stackable messages, both
 * persistent and transient.
 *
 * @author Mark Scherer
 * @copyright 2012 Mark Scherer
 * @license MIT
 */
class FlashComponent extends Component {

	public $components = array('Session');

	/**
	 * For automatic startup
	 * for this helper the controller has to be passed as reference
	 *
	 * @return void
	 */
	public function initialize(Controller $Controller) {
		parent::initialize($Controller);

		$this->Controller = $Controller;
	}

	/**
	 * Called after the Controller::beforeRender(), after the view class is loaded, and before the
	 * Controller::render()
	 *
	 * @param object $Controller Controller with components to beforeRender
	 * @return void
	 */
	public function beforeRender(Controller $Controller) {
		if (Configure::read('Common.messages') !== false && $messages = $this->Session->read('Message')) {
			foreach ($messages as $message) {
				$this->flashMessage($message['message'], 'error');
			}
			$this->Session->delete('Message');
		}

		if ($this->Controller->request->is('ajax')) {
			$ajaxMessages = array_merge(
				(array)$this->Session->read('messages'),
				(array)Configure::read('messages')
			);
			// The header can be read with JavaScript and a custom Message can be displayed
			$this->Controller->response->header('X-Ajax-Flashmessage', json_encode($ajaxMessages));

			$this->Session->delete('messages');
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

		$old = (array)$this->Session->read('messages');
		if (isset($old[$type]) && count($old[$type]) > 99) {
			array_shift($old[$type]);
		}
		$old[$type][] = $message;
		$this->Session->write('messages', $old);
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
