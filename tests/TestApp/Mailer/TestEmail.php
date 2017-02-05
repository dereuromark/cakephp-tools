<?php
namespace TestApp\Mailer;

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
	public function wrap($text, $length = Email::LINE_LENGTH_MUST) {
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
