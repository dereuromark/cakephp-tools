<?php
App::uses('AppHelper', 'View/Helper');
App::uses('FlashComponent', 'Tools.Controller/Component');
App::uses('Hash', 'Utility');

/**
 * Flash helper
 *
 * Partial backport from the 3.x one to ease migration.
 */
class FlashHelper extends AppHelper {

	public $helpers = ['Session'];

	protected $_defaultConfig = [
		'useElements' => false, //Set to true to use 3.x flash message rendering via Elements
	];

	public function __construct(View $View, $settings = []) {
		$defaults = (array)Configure::read('Flash') + $this->_defaultConfig;
		$settings += $defaults;
		parent::__construct($View, $settings);
	}

	/**
	 * Displays all flash messages.
	 *
	 * TODO: export div wrapping method (for static messaging on a page)
	 *
	 * @param array $types Types to output. Defaults to all if none are specified.
	 * @return string HTML
	 */
	public function flash(array $types = []) {
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
	 * $escape is deprecated as it is already part of the message
	 *
	 * @param array|string $message String to output.
	 * @param string $type Type (success, warning, error, info)
	 * @param bool|null $escape Set to false to disable escaping.
	 * @return string HTML
	 */
	public function message($msg, $type = 'info', $escape = null) {
		if ($escape === null && is_array($msg) && !isset($msg['escape'])) {
			$msg['escape'] = true;
		}
		$escape = is_array($msg) && isset($msg['escape']) ? $msg['escape'] : true;

		$html = '<div class="flash-messages flashMessages">';
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
		if (!is_array($msg)) {
			if (!empty($msg)) {
				return '<div class="message' . (!empty($type) ? ' ' . $type : '') . '">' . $msg . '</div>';
			}
			return '';
		}
		$msg['type'] = $type;
		return $this->_View->element($msg['element'], $msg);
	}

	/**
	 * Adds a message on the fly.
	 *
	 * Only works with static Configure configuration.
	 *
	 * @param string $msg
	 * @param string $class
	 * @return void
	 */
	public function addTransientMessage($msg, $options = []) {
		FlashComponent::transientMessage($msg, $options);
	}

	/**
	 * CommonHelper::transientFlashMessage()
	 *
	 * @param mixed $msg
	 * @param mixed $class
	 * @return void
	 * @deprecated Use addFlashMessage() instead
	 */
	public function transientFlashMessage($msg, $options = []) {
		$this->addFlashMessage($msg, $options);
	}

}
