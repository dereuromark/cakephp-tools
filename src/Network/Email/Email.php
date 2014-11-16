<?php
namespace Tools\Network\Email;

use Cake\Network\Email\Email as CakeEmail;
use Tools\Utility\Utility;
use Tools\Utility\Mime;

class Email extends CakeEmail {

	protected $_wrapLength = null;

	protected $_priority = null;

	public function __construct($config = null) {
		if ($config === null) {
			$config = 'default';
		}
		parent::__construct($config);
die(debug($this));
		//$this->resetAndSet();
	}

	/**
	 * Set/Get wrapLength
	 *
	 * @param int $length Must not be more than CakeEmail::LINE_LENGTH_MUST
	 * @return int|CakeEmail
	 */
	public function wrapLength($length = null) {
		if ($length === null) {
			return $this->_wrapLength;
		}
		$this->_wrapLength = $length;
		return $this;
	}

	/**
	 * Set/Get priority
	 *
	 * @param int $priority 1 (highest) to 5 (lowest)
	 * @return int|CakeEmail
	 */
	public function priority($priority = null) {
		if ($priority === null) {
			return $this->_priority;
		}
		$this->_priority = $priority;
		return $this;
	}

	/**
	 * Fix line length
	 *
	 * @overwrite
	 * @param string $message Message to wrap
	 * @return array Wrapped message
	 */
	protected function _wrap($message, $wrapLength = CakeEmail::LINE_LENGTH_MUST) {
		if ($this->_wrapLength !== null) {
			$wrapLength = $this->_wrapLength;
		}
		return parent::_wrap($message, $wrapLength);
	}

	/**
	 * EmailLib::resetAndSet()
	 *
	 * @return void
	 */
	public function reset() {
		parent::reset();
		$this->_priority = null;
		$this->_wrapLength = null;

		$this->_error = null;
		$this->_debug = null;

		if ($fromEmail = Configure::read('Config.systemEmail')) {
			$fromName = Configure::read('Config.systemName');
		} else {
			$fromEmail = Configure::read('Config.adminEmail');
			$fromName = Configure::read('Config.adminName');
		}
		if (!$fromEmail) {
			throw new \RuntimeException('You need to either define Config.systemEmail or Config.adminEmail in Configure.');
		}
		$this->from($fromEmail, $fromName);

		if ($xMailer = Configure::read('Config.xMailer')) {
			$this->addHeaders(array('X-Mailer' => $xMailer));
		}
	}

}
