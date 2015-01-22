<?php
namespace Tools\View\Helper;

use Cake\View\Helper;

/**
 * Format helper with basic html snippets
 *
 * TODO: make snippets more "css and background image" (instead of inline img links)
 *
 * @author Mark Scherer
 * @license MIT
 */
class ObfuscateHelper extends Helper {

	/**
	 * Other helpers used
	 *
	 * @var array
	 */
	public $helpers = ['Html'];

	/**
	 * It is still believed that encoding will stop spam-bots being able to find your email address.
	 * Nevertheless, encoded email address harvester are on the way (http://www.dreamweaverfever.com/experiments/spam/).
	 *
	 * Helper Function to Obfuscate Email by inserting a span tag (not more! not very secure on its own...)
	 * each part of this mail now does not make sense anymore on its own
	 * (striptags will not work either)
	 *
	 * @param string email: necessary (and valid - containing one @)
	 * @return string
	 */
	public function encodeEmail($mail) {
		$pieces = explode('@', $mail);
		if (count($pieces) < 2) {
			return $mail;
		}
		list($mail1, $mail2) = $pieces;
		$encMail = $this->encodeText($mail1) . '<span>@</span>' . $this->encodeText($mail2);
		return $encMail;
	}

	/**
	 * Obfuscates Email (works without JS!) to avoid spam bots to get it
	 *
	 * @param string mail: email to encode
	 * @param string text: optional (if none is given, email will be text as well)
	 * @param array attributes: html tag attributes
	 * @param array params: ?subject=y&body=y to be attached to "mailto:xyz"
	 * @return string Save string with JS generated link around email (and non JS fallback)
	 */
	public function encodeEmailUrl($mail, $text = null, $params = [], $attr = []) {
		if (empty($class)) {
			$class = 'email';
		}

		$defaults = [
			'title' => __d('tools', 'for use in an external mail client'),
			'class' => 'email',
			'escape' => false
		];

		if (empty($text)) {
			$text = $this->encodeEmail($mail);
		}

		$encMail = 'mailto:' . $mail;

		// additionally there could be a span tag in between: email<span syle="display:none"></span>@web.de
		$querystring = '';
		foreach ($params as $key => $val) {
			if ($querystring) {
				$querystring .= "&$key=" . rawurlencode($val);
			} else {
				$querystring = "?$key=" . rawurlencode($val);
			}
		}

		$attr = array_merge($defaults, $attr);

		$xmail = $this->Html->link('', $encMail . $querystring, $attr);
		$xmail1 = mb_substr($xmail, 0, count($xmail) - 5);
		$xmail2 = mb_substr($xmail, -4, 4);

		$len = mb_strlen($xmail1);
		$i = 0;
		while ($i < $len) {
			$c = mt_rand(2, 6);
			$par[] = (mb_substr($xmail1, $i, $c));
			$i += $c;
		}
		$join = implode('\'+ \'', $par);

		return '<script language=javascript><!--
	document.write(\'' . $join . '\');
	//--></script>
		' . $text . '
	<script language=javascript><!--
	document.write(\'' . $xmail2 . '\');
	//--></script>';
	}

	/**
	 * Encodes Piece of Text (without usage of JS!) to avoid spam bots to get it
	 *
	 * @param STRING text to encode
	 * @return string (randomly encoded)
	 */
	public function encodeText($text) {
		$encmail = '';
		for ($i = 0; $i < mb_strlen($text); $i++) {
			$encMod = mt_rand(0, 2);
			switch ($encMod) {
			case 0: // None
				$encmail .= mb_substr($text, $i, 1);
				break;
			case 1: // Decimal
				$encmail .= "&#" . ord(mb_substr($text, $i, 1)) . ';';
				break;
			case 2: // Hexadecimal
				$encmail .= "&#x" . dechex(ord(mb_substr($text, $i, 1))) . ';';
				break;
			}
		}
		return $encmail;
	}

	/**
	 * test@test.de becomes t..t@t..t.de
	 *
	 * @param string $email: valid(!) email address
	 * @return string
	 */
	public static function hideEmail($mail) {
		$mailParts = explode('@', $mail, 2);
		$domainParts = explode('.', $mailParts[1], 2);

		$user = mb_substr($mailParts[0], 0, 1) . '..' . mb_substr($mailParts[0], -1, 1);
		$domain = mb_substr($domainParts[0], 0, 1) . '..' . mb_substr($domainParts[0], -1, 1) . '.' . $domainParts[1];
		return $user . '@' . $domain;
	}

	/**
	 * Word Censoring Function
	 *
	 * Supply a string and an array of disallowed words and any
	 * matched words will be converted to #### or to the replacement
	 * word you've submitted.
	 *
	 * @param string	the text string
	 * @param string	the array of censoered words
	 * @param string	the optional replacement value
	 * @return string
	 */
	public function wordCensor($str, $censored, $replacement = null) {
		if (empty($censored)) {
			return $str;
		}
		$str = ' ' . $str . ' ';

		// \w, \b and a few others do not match on a unicode character
		// set for performance reasons. As a result words like ..ber
		// will not match on a word boundary. Instead, we'll assume that
		// a bad word will be bookended by any of these characters.
		$delim = '[-_\'\"`() {}<>\[\]|!?@#%&,.:;^~*+=\/ 0-9\n\r\t]';

		foreach ($censored as $badword) {
			if ($replacement !== null) {
				$str = preg_replace("/({$delim})(" . str_replace('\*', '\w*?', preg_quote($badword, '/')) . ")({$delim})/i", "\\1{$replacement}\\3", $str);
			} else {
				$str = preg_replace_callback("/({$delim})(" . str_replace('\*', '\w*?', preg_quote($badword, '/')) . ")({$delim})/i", function ($matches) {
					return $matches[1] . str_repeat('#', strlen($matches[2])) . $matches[3];
				}, $str);
			}
		}

		return trim($str);
	}

}
