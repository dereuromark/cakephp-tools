<?php

define('CAPTCHA_MIN_TIME', 2); # seconds the form will need to be filled in by a human
//define('CAPTCHA_MAX_TIME', HOUR);	# seconds the form will need to be submitted in

/**
 * CaptchaBehavior
 * NOTES: needs captcha helper
 *
 * validate passive or active captchas
 * active: session-based, db-based or hash-based
 * 2009-12-12 ms
 */
class CaptchaBehavior extends ModelBehavior {

	private $options = array(
		'dummyField' => 'homepage',
		'method' => 'hash',
		'checkSession' => false,
		'checkIp' => false,
		'salt' => '',
		'type' => 'active',
		# behaviour only:
		'minTime' => CAPTCHA_MIN_TIME,
		'maxTime' => 0,
		'log' => false,
	);

	private $dummyField = 'homepage';

	private $methods = array('hash', 'db', 'session');
	private $method = 'hash';

	private $log = false;
	private $error = '';
	private $internalError = '';
	//private $types = array('passive','active','both');
	//private $useSession = false;

	function setup(&$Model, $settings) {

		# bootstrap configs
		$configs = (array )Configure::read('Captcha');
		if (!empty($configs)) {
			$this->options = array_merge($this->options, $configs);
		}

		# local configs in specific action
		if (!empty($settings['minTime'])) {
			$this->options['minTime'] = (int)$settings['minTime'];
		}
		if (!empty($settings['maxTime'])) {
			$this->options['maxTime'] = (int)$settings['maxTime'];
		}
		if (isset($settings['log'])) {
			$this->options['log'] = (bool)$settings['log'];
		}

		/*
		better:

		if (!isset($this->settings[$Model->alias])) {
		$this->settings[$Model->alias] = array(
		'option1_key' => 'option1_default_value'
		);
		}
		$this->settings[$Model->alias] = array_merge($this->settings[$Model->alias], (array)$settings);
		*/
	}


	public function beforeValidate(&$Model, &$queryData) {
		$this->Model = &$Model;

		if (!$this->validateCaptchaTime($this->Model->data[$this->Model->name])) {
			$this->Model->invalidate('captcha', 'captchaResultTooFast', true);

		} elseif (!$this->validateDummyField($this->Model->data[$this->Model->name])) {
			$this->Model->invalidate('captcha', 'captchaIllegalContent', true);

		} elseif ($this->options['type'] == 'active' && !$this->validateCaptcha($this->Model->data[$this->Model->name])) {
			$this->Model->invalidate('captcha', 'captchaResultIncorrect', true);

		}
		unset($this->Model->data[$this->Model->name]['captcha']);
		unset($this->Model->data[$this->Model->name]['captcha_hash']);
		unset($this->Model->data[$this->Model->name]['captcha_time']);
		return true;
	}

	/**
	 * return the current used field names to be passed in whitelist etc
	 * 2010-01-22 ms
	 */
	public function fields() {
		$list = array('captcha', 'captcha_hash', 'captcha_time');
		$list[] = $this->options['dummyField'];
		return $list;
	}


	private function validateDummyField($data) {
		$dummyField = $this->options['dummyField'];
		if (!empty($data[$dummyField])) {
			# dummy field not empty - SPAM!
			return $this->error('Illegal content', 'DummyField = \''.$data[$dummyField].'\'');
		}
		return true;
	}


	/**
	 * flood protection by time
	 * TODO: SESSION based one as alternative
	 */
	private function validateCaptchaTime($data) {
		if ($this->options['minTime'] <= 0) {
			return true;
		}

		if (empty($data['captcha_hash']) || empty($data['captcha_time']) || $data['captcha_time'] > time() - $this->options['minTime']) {
			// trigger error - SPAM!!!
			return false;
		}

		# //TODO: max?
		if (false) {
			return false;
		}

		return true;
	}

	/**
	 * flood protection by false fields and math code
	 * TODO: build in floodProtection (max Trials etc)
	 * TODO: SESSION based one as alternative
	 */
	private function validateCaptcha($data) {


		if (!isset($data['captcha'])) {
			# form inputs missing? SPAM!
			return $this->error('Captcha content missing');
		}

		$hash = $this->buildHash($data);

		if ($data['captcha_hash'] == $hash) {
			return true;
		}
		# wrong captcha content or session expired
		return $this->error('Captcha incorrect', 'SubmittedResult = \''.$data['captcha'].'\'');
	}

	/**
	 * return error message (or empty string if none)
	 * @return string
	 */
	public function errors() {
		return $this->error;
	}

	/**
	 * only neccessary if there is more than one request per model
	 * 2009-12-18 ms
	 */
	public function reset() {
		$this->error = '';
	}

	/**
	 * build and log error message
	 * 2009-12-18 ms
	 */
	private function error($msg = null, $internalMsg = null) {
		if (!empty($msg)) {
			$this->error = $msg;
		}
		if (!empty($internalMsg)) {
			$this->internalError = $internalMsg;
		}

		if ($this->log) {
			$this->logAttempt();
		}
		return false;
	}

	function buildHash($data) {
		$hashValue = date(FORMAT_DB_DATE, (int)$data['captcha_time']).'_';
		$hashValue .= ($this->options['checkSession'])?session_id().'_' : '';
		$hashValue .= ($this->options['checkIp'])?env('REMOTE_ADDR').'_' : '';
		$hashValue .= $data['captcha'].'_'.$this->options['salt'];
		return Security::hash($hashValue);
	}

	/**
	 * logs attempts
	 * @param bool errorsOnly (only if error occured, otherwise always)
	 * @returns null if not logged, true otherwise
	 * 2009-12-18 ms
	 */
	private function logAttempt($errorsOnly = true) {
		if ($errorsOnly === true && empty($this->error) && empty($this->internalError)) {
			return null;
		}

		App::import('Component', 'RequestHandler');
		$msg = 'Ip \''.RequestHandlerComponent::getClientIP().'\', Agent \''.env('HTTP_USER_AGENT').'\', Referer \''.env('HTTP_REFERER').'\', Host-Referer \''.RequestHandlerComponent::getReferer().'\'';
		if (!empty($this->error)) {
			$msg .= ', '.$this->error;
		}
		if (!empty($this->internalError)) {
			$msg .= ' ('.$this->internalError.')';
		}
		$this->log($msg, 'captcha');
		return true;
	}

}

?>