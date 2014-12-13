<?php
App::uses('AppHelper', 'View/Helper');
App::uses('FlashComponent', 'Tools.Controller/Component');
App::uses('Hash', 'Utility');

/**
 * Flash helper
 */
class FlashHelper extends AppHelper {

	public $helpers = array('Session');

	/**
	 * Display all flash messages.
	 *
	 * TODO: export div wrapping method (for static messaging on a page)
	 *
	 * @param array $types Types to output. Defaults to all if none are specified.
	 * @return string HTML
	 */
	public function flash(array $types = array()) {
		// Get the messages from the session
		$messages = (array)$this->Session->read('messages');
		$cMessages = (array)Configure::read('messages');
		if (!empty($cMessages)) {
			$messages = (array)Hash::merge($messages, $cMessages);
		}
		$html = '';
		if (!empty($messages)) {
			$html = '<div class="flash-messages flashMessages">';

			if ($types) {
				foreach ($types as $type) {
					// Add a div for each message using the type as the class.
					foreach ($messages as $messageType => $msgs) {
						if ($messageType !== $type) {
							continue;
						}
						foreach ((array)$msgs as $msg) {
							$html .= $this->_message($msg, $messageType);
						}
					}
				}
			} else {
				foreach ($messages as $messageType => $msgs) {
					foreach ((array)$msgs as $msg) {
						$html .= $this->_message($msg, $messageType);
					}
				}
			}
			$html .= '</div>';
			if ($types) {
				foreach ($types as $type) {
					CakeSession::delete('messages.' . $type);
					Configure::delete('messages.' . $type);
				}
			} else {
				CakeSession::delete('messages');
				Configure::delete('messages');
			}
		}

		return $html;
	}

	/**
	 * Outputs a single flash message directly.
	 * Note that this does not use the Session.
	 *
	 * @param string $message String to output.
	 * @param string $type Type (success, warning, error, info)
	 * @param bool $escape Set to false to disable escaping.
	 * @return string HTML
	 */
	public function message($msg, $type = 'info', $escape = true) {
		$html = '<div class="flash-messages flashMessages">';
		if ($escape) {
			$msg = h($msg);
		}
		$html .= $this->_message($msg, $type);
		$html .= '</div>';
		return $html;
	}

	/**
	 * Formats a message
	 *
	 * @param string $msg Message to output.
	 * @param string $type Type that will be formatted to a class tag.
	 * @return string
	 */
	protected function _message($msg, $type) {
		if (!empty($msg)) {
			return '<div class="message' . (!empty($type) ? ' ' . $type : '') . '">' . $msg . '</div>';
		}
		return '';
	}

	/**
	 * Add a message on the fly
	 *
	 * @param string $msg
	 * @param string $class
	 * @return void
	 */
	public function addTransientMessage($msg, $class = null) {
		FlashComponent::transientMessage($msg, $class);
	}

	/**
	 * CommonHelper::transientFlashMessage()
	 *
	 * @param mixed $msg
	 * @param mixed $class
	 * @return void
	 * @deprecated Use addFlashMessage() instead
	 */
	public function transientFlashMessage($msg, $class = null) {
		$this->addFlashMessage($msg, $class);
	}

}
