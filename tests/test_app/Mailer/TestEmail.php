<?php

namespace App\Mailer;

use Cake\Mailer\Message;
use Tools\Mailer\Email;

/**
 * Help to test Email
 */
class TestEmail extends Email {

	/**
	 * Wrap to protected method
	 *
	 * @param string $text
	 * @param int $length
	 * @return array
	 */
	public function wrap($text, $length = Message::LINE_LENGTH_MUST) {
		return parent::_wrap($text, $length);
	}

	/**
	 * @param string $attribute
	 * @return mixed
	 */
	public function getProtected($attribute) {
		$attribute = '_' . $attribute;
		return $this->$attribute;
	}

}
