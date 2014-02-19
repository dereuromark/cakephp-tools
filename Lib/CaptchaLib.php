<?php

App::uses('Security', 'Utility');
if (!defined('FORMAT_DB_DATE')) {
	define('FORMAT_DB_DATETIME', 'Y-m-d H:i:s');
}

/**
 * Main utility for captchas.
 * Used by captcha helper and behavior.
 */
class CaptchaLib {

	public static $defaults = array(
		'dummyField' => 'homepage',
		'method' => 'hash',
		'type' => 'both',
		'checkSession' => false,
		'checkIp' => false,
		'salt' => '',
	);

	// what type of captcha
	public static $types = array('passive', 'active', 'both');

	// what method to use
	public static $methods = array('hash', 'db', 'session');

	/**
	 * @param array $data:
	 * - captcha_time, result/captcha
	 * @param array $options:
	 * - salt (required)
	 * - checkSession, checkIp, hashType (all optional)
	 * @return string
	 */
	public static function buildHash($data, $options, $init = false) {
		if ($init) {
			$data['captcha_time'] = time();
			$data['captcha'] = $data['result'];
		}

		$hashValue = date(FORMAT_DB_DATETIME, (int)$data['captcha_time']) . '_';
		$hashValue .= ($options['checkSession']) ? session_id() . '_' : '';
		$hashValue .= ($options['checkIp']) ? env('REMOTE_ADDR') . '_' : '';
		if (empty($options['type']) || $options['type'] !== 'passive') {
			$hashValue .= $data['captcha'];
		}
		$type = isset($options['hashType']) ? $options['hashType'] : null;
		return Security::hash($hashValue, $type, $options['salt']);
	}

}
