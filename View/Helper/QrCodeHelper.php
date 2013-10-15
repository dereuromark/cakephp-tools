<?php
App::uses('AppHelper', 'View/Helper');
/**
 * Example Url:
 * http://chart.apis.google.com/chart?cht=qr&chs=400x400&chl=SomeText
 */

/*
if (!defined('QS_CODE_MIN_SIZE')) {
	define('QS_CODE_MIN_SIZE', 58);
}
if (!defined('QS_CODE_MAX_SIZE')) {
	define('QS_CODE_MAX_SIZE', 540);
}
if (!defined('QS_CODE_DEFAULT_SIZE')) {
	define('QS_CODE_DEFAULT_SIZE', 74);
}

if (!defined('QS_CODE_DEFAULT_LEVEL')) {
	define('QS_CODE_DEFAULT_LEVEL', 'L');
}
*/

/**
 * QR Code Helper
 * based on google chart api
 * @see http://code.google.com/intl/de-DE/apis/chart/types.html#qrcodes
 *
 * alternative service api / engine: http://goqr.me/api-description/ (not available right now)
 * or: http://qrcode.kaywa.com/img.php
 *
 * NOTE: urls have a 2k limit - for the total amount of 4296 chars (7089 for numeric values only) you will need to send it via post
 *
 * TODO: set size according to text length automatically
 */
class QrCodeHelper extends AppHelper {

	public $helpers = array('Html');

	const MIN_SIZE = 58; # not readable anymore below this value
	const MAX_SIZE = 540; # max of 300000 pixels
	const DEFAULT_SIZE = 74; # 2x size
	const DEFAULT_LEVEL = 'L'; # highest correction level

	const SIZE_L = 58;
	const SIZE_M = 66;
	const SIZE_Q = 66;
	const SIZE_H = 74;

	public $engine = 'google';

	public $url = 'http://chart.apis.google.com/chart?';

	/**
	 * necessary
	 * - chl: string $text
	 * - choe: string $outputEncoding
	 * - chs: size (...x...)
	 */
	public $options = array('cht' => 'qr', 'chl' => '', 'choe' => '', 'chs' => '');

	public $ecLevels = array('H', 'Q', 'M', 'L'); # 30%..7%

	public $formattingTypes = array('url' => 'http', 'tel' => 'tel', 'sms' => 'smsto', 'card' => 'mecard');

	public function __construct(View $View, $settings = array()) {
		parent::__construct($View, $settings);
		$this->reset();
	}

	/**
	 * Main barcode display function
	 * @param string $text (utf8 encoded)
	 * @param array $imageOptions
	 * NOTE: set size or level manually prior to calling this method
	 */
	public function image($text, $options = array()) {
		return $this->Html->image($this->uri($text), $options);
	}

	/**
	 * Just the url - without image tag
	 * Note: cannot be url() due to AppHelper conflicts
	 *
	 * @param string $text
	 * @return string Url
	 */
	public function uri($text) {
		$params = array();
		$params['chl'] = rawurlencode($text);
		return $this->_uri($params);
	}

	/**
	 * @return string Url
	 */
	protected function _uri($params = array()) {
		$params = array_merge($this->options, $params);
		$pieces = array();
		foreach ($params as $key => $value) {
			$pieces[] = $key . '=' . $value;
		}
		return $this->url . implode('&', $pieces);
	}

	/**
	 * Format a text in a specific format
	 * - url, sms, tel, email, market, geo
	 * @return string formattedText
	 */
	public function formatText($text, $type = null) {
		switch ($type) {
			case 'text':
				break;
			case 'url':
				$text = $this->Html->url($text, true);
				break;
			case 'sms':
				$text = 'smsto:' . implode(':', (array)$text);
				break;
			case 'tel':
				$text = 'tel:' . $text;
				break;
			case 'email':
				$text = 'mailto:' . $text;
				break;
			case 'geo':
				$text = 'geo:' . implode(',', (array)$text); #like 77.1,11.8
				break;
			case 'market':
				$text = 'market://search?q=pname:' . $text;
		}
		return $text;
	}

	/**
	 * Generate mecard string
	 * 1: name, nickname, note, birthday, sound
	 * 1..n (as array or string): address, tel, url, email
	 * for details on cards see:
	 * http://www.nttdocomo.co.jp/english/service/imode/make/content/barcode/function/application/addressbook/index.html
	 * example: MECARD: N:Docomo,Taro; SOUND:docomotaro; TEL:03XXXXXXXX; EMAIL:d@e.de;
	 * @return string mecard
	 */
	public function formatCard($data) {
		$data = (array)$data;
		$res = array();
		foreach ($data as $key => $val) {
			switch ($key) {
				case 'name':
					$res[] = 'N:' . $val; # //TODO: support array
					break;
				case 'nickname':
					$res[] = 'NICKNAME:' . $val;
					break;
				case 'sound':
					$res[] = 'SOUND:' . $val;
					break;
				case 'note':
					$val = str_replace(';', ',', $val);
					$res[] = 'NOTE:' . $val; //TODO: remove other invalid characters
					break;
				case 'birthday':
					if (strlen($val) !== 8) {
						$val = substr($val, 0, 4) . substr($val, 6, 2) . substr($val, 10, 2);
					}
					$res[] = 'BDAY:' . $val;
					break;
				case 'tel':
					$val = (array)$val;
					foreach ($val as $v) {
						$res[] = 'TEL:' . $v;
					}
					break;
				case 'video':
					$val = (array)$val;
					foreach ($val as $v) {
						$res[] = 'TEL-AV:' . $v;
					}
					break;
				case 'address':
					$val = (array)$val;
					foreach ($val as $v) {
						$res[] = 'ADR:' . $v; //TODO: reformat (array etc)
					}
					break;
				case 'org':
					$val = (array)$val;
					foreach ($val as $v) {
						$res[] = 'ORG:' . $v;
					}
					break;
				case 'role':
					$val = (array)$val;
					foreach ($val as $v) {
						$res[] = 'ROLE:' . $v;
					}
					break;
				case 'email':
					$val = (array)$val;
					foreach ($val as $v) {
						$res[] = 'EMAIL:' . $v;
					}
					break;
				case 'url':
					$val = (array)$val;
					foreach ($val as $v) {
						$res[] = 'URL:' . $this->Html->url($v, true);
					}
					break;
			}
		}

		return 'MECARD:' . implode(';', $res) . ';';
	}

	/**
	 * //TODO
	 * calendar event
	 * e.g.: BEGIN:VEVENT SUMMARY:dfdfd DTSTART:20100226T092900Z DTEND:20100226T102900Z END:VEVENT
	 * @see http://zxing.appspot.com/generator/
	 */
	public function formatEvent() {
	}

	/**
	 * QrCodeHelper::format()
	 *
	 * @param mixed $protocol
	 * @param mixed $string
	 * @return string
	 */
	public function format($protocol, $string) {
		return $protocol . ':' . $string;
	}

	/**
	 * Change size
	 * result format: chs=<size>x<size>
	 * @return boolean Success
	 *
	 * //TODO: automatic detection
	 * //default is 2x size (plus margin) of typical QR version for the error correction level (L=V.2, M/Q=V.3, H=V.4)
	 * //$ecCodes = array('L' => 58, 'M' => 66, 'Q' => 66, 'H' => 74);
	 */
	public function setSize($value) {
		if ($value === 'auto') {
			//TODO
		}
		$value = (int)$value;
		if ($value >= self::MIN_SIZE && $value <= self::MAX_SIZE) {
			$this->options['chs'] = $value . 'x' . $value;
			return true;
		}
		return false;
	}

	/**
	 * Change level and margin - optional
	 * result format: chld=<EC level>|<margin>
	 * @return boolean Success
	 */
	public function setLevel($level, $margin = null) {
		if (in_array($level, $this->ecLevels)) {
			if ($margin === null) {
				$margin = 4; # minimum
			}
			$this->options['chld'] = strtoupper($level) . '|' . $margin;
			return true;
		}
		return false;
	}

	public function setEncoding() {
		//TODO
	}

	/**
	 * QrCodeHelper::reset()
	 *
	 * @return void
	 */
	public function reset() {
		$this->setSize(self::DEFAULT_SIZE);
		//$this->setLevel(QS_CODE_DEFAULT_LEVEL);
		$this->options['chld'] = '';
		$this->options['choe'] = Configure::read('App.encoding');
	}

	/**
	 * Show current options - for debugging only
	 */
	public function debug() {
		return $this->options;
	}

	/**
	 * 25 => 21x21 (L)
	 * ...
	 * 4000 => 547x547 (L)
	 * @param integer $length
	 * @return integer size
	 */
	protected function _findSuitableSize() {
	}

}
