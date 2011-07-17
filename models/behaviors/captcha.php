<?php

define('CAPTCHA_MIN_TIME', 3); # seconds the form will need to be filled in by a human
define('CAPTCHA_MAX_TIME', HOUR);	# seconds the form will need to be submitted in

/**
 * CaptchaBehavior
 * NOTES: needs captcha helper
 *
 * validate passive or active captchas
 * active: session-based, db-based or hash-based
 * 2009-12-12 ms
 */
class CaptchaBehavior extends ModelBehavior {

	protected $defaults = array(
		'minTime' => CAPTCHA_MIN_TIME,
		'maxTime' => CAPTCHA_MAX_TIME,
		'log' => false, # log errors
		'hashType' => null,
	);

	protected $error = '';
	protected $internalError = '';
	//
	//protected $useSession = false;

	function setup(Model $Model, $settings) {
		App::import('Lib', 'Tools.CaptchaLib');
		$this->defaults = array_merge(CaptchaLib::$defaults, $this->defaults);
		$this->Model = $Model;
		
		# bootstrap configs
		$this->settings[$Model->alias] = $this->defaults;
		$settings = (array)Configure::read('Captcha');
		if (!empty($settings)) {
			$this->settings[$Model->alias] = array_merge($this->settings[$Model->alias], $settings);
		}

		# local configs in specific action
		if (!empty($settings['minTime'])) {
			$this->settings[$Model->alias]['minTime'] = (int)$settings['minTime'];
		}
		if (!empty($settings['maxTime'])) {
			$this->settings[$Model->alias]['maxTime'] = (int)$settings['maxTime'];
		}
		if (isset($settings['log'])) {
			$this->settings[$Model->alias]['log'] = (bool)$settings['log'];
		}
	}


	public function beforeValidate(Model $Model, &$queryData) {
		parent::beforeValidate($Model);
		
		if (!empty($this->Model->whitelist)) {
			$this->Model->whitelist = array_merge($Model->whitelist, $this->fields());
		}

		if (!$this->_validateCaptchaMinTime($Model->data[$Model->alias])) {
			$this->Model->invalidate('captcha', 'captchaResultTooFast', true);

		} elseif (!$this->_validateCaptchaMaxTime($Model->data[$Model->alias])) {
			$this->Model->invalidate('captcha', 'captchaResultTooLate', true);
			
		} elseif (!$this->_validateDummyField($Model->data[$Model->alias])) {
			$this->Model->invalidate('captcha', 'captchaIllegalContent', true);

		} elseif (in_array($this->settings[$Model->alias]['type'], array('active', 'both')) && !$this->_validateCaptcha($Model->data[$Model->alias])) {
			$this->Model->invalidate('captcha', 'captchaResultIncorrect', true);

		}
		
		unset($Model->data[$Model->alias]['captcha']);
		unset($Model->data[$Model->alias]['captcha_hash']);
		unset($Model->data[$Model->alias]['captcha_time']);
		return true;
	}

	/**
	 * return the current used field names to be passed in whitelist etc
	 * 2010-01-22 ms
	 */
	public function fields() {
		$list = array('captcha', 'captcha_hash', 'captcha_time');
		$list[] = $this->settings[$this->Model->alias]['dummyField'];
		return $list;
	}


	protected function _validateDummyField($data) {
		$dummyField = $this->settings[$this->Model->alias]['dummyField'];
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
	protected function _validateCaptchaMinTime($data) {
		if ($this->settings[$this->Model->alias]['minTime'] <= 0) {
			return true;
		}
		if (isSet($data['captcha_hash']) && isSet($data['captcha_time'])) {
			if ($data['captcha_time'] < time() - $this->settings[$this->Model->alias]['minTime']) {
				return true;
			}
		}
		return false;
	}

	/**
	 * validates maximum time
	 * 
	 * @param array $data
	 * @return bool 
	 */
	protected function _validateCaptchaMaxTime($data) {
		if ($this->settings[$this->Model->alias]['maxTime'] <= 0) {
			return true;
		}
		if (isSet($data['captcha_hash']) && isSet($data['captcha_time'])) {
			if ($data['captcha_time'] + $this->settings[$this->Model->alias]['maxTime'] > time()) {
				return true;
			}
		}

		return false;
	}	

	/**
	 * flood protection by false fields and math code
	 * TODO: build in floodProtection (max Trials etc)
	 * TODO: SESSION based one as alternative
	 */
	protected function _validateCaptcha($data) {


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
	protected function error($msg = null, $internalMsg = null) {
		if (!empty($msg)) {
			$this->error = $msg;
		}
		if (!empty($internalMsg)) {
			$this->internalError = $internalMsg;
		}

		
		$this->logAttempt();
		return false;
	}

	function buildHash($data) {
		return CaptchaLib::buildHash($data, $this->settings[$this->Model->alias]);
	}

	/**
	 * logs attempts
	 * @param bool errorsOnly (only if error occured, otherwise always)
	 * @returns null if not logged, true otherwise
	 * 2009-12-18 ms
	 */
	protected function logAttempt($errorsOnly = true) {
		if ($errorsOnly === true && empty($this->error) && empty($this->internalError)) {
			return null;
		}
		if (!$this->settings[$this->Model->alias]['log']) {
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


