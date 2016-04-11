<?php

namespace Tools\View\Helper;

use Cake\Core\Configure;
use Cake\Utility\Hash;
use Cake\View\Helper;
use Tools\Controller\Component\FlashComponent;

/**
 * Flash helper
 *
 * @author Mark Scherer
 */
class FlashHelper extends Helper {

	/**
	 * Display all flash messages.
	 *
	 * @param array $types Types to output. Defaults to all if none are specified.
	 * @return string HTML
	 * @deprecated Use render() instead
	 */
	public function flash(array $types = []) {
		return $this->render($types);
	}

	/**
	 * Display all flash messages.
	 *
	 * TODO: export div wrapping method (for static messaging on a page)
	 *
	 * @param array $types Types to output. Defaults to all if none are specified.
	 * @return string HTML
	 */
	public function render(array $types = []) {
		// Get the messages from the session
		$messages = (array)$this->request->session()->read('FlashMessage');
		$cMessages = (array)Configure::read('FlashMessage');
		if (!empty($cMessages)) {
			$messages = (array)Hash::merge($messages, $cMessages);
		}
		$html = '';
		if (!empty($messages)) {
			$html = '<div class="flash-messages">';

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
					$this->request->session()->delete('FlashMessage.' . $type);
					Configure::delete('FlashMessage.' . $type);
				}
			} else {
				$this->request->session()->delete('FlashMessage');
				Configure::delete('FlashMessage');
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
	public function message($message, $type = 'info', $escape = true) {
		$html = '<div class="flash-messages">';
		if ($escape) {
			$msg = h($message);
		}
		$html .= $this->_message($message, $type);
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
	 * @param string|null $class
	 * @return void
	 */
	public function addTransientMessage($msg, $class = null) {
		FlashComponent::transientMessage($msg, $class);
	}

}
