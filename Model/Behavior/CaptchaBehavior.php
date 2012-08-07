<?php

define('CAPTCHA_MIN_TIME', 3); # seconds the form will need to be filled in by a human
define('CAPTCHA_MAX_TIME', HOUR);	# seconds the form will need to be submitted in

App::uses('ModelBehavior', 'Model');
App::uses('CaptchaLib', 'Tools.Lib');
App::uses('CommonComponent', 'Tools.Controller/Component');

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

	public function setup(Model $Model, $settings = array()) {
		$defaults = array_merge(CaptchaLib::$defaults, $this->defaults);
		$this->Model = $Model;

		# bootstrap configs
		$this->settings[$Model->alias] = $defaults;
		$this->settings[$Model->alias] = array_merge($this->settings[$Model->alias], (array)Configure::read('Captcha'));
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

		//parent::setup($Model, $settings);
	}


	public function beforeValidate(Model $Model) {
		parent::beforeValidate($Model);
		if (!empty($this->Model->whitelist)) {
			$this->Model->whitelist = array_merge($Model->whitelist, $this->fields());
		}
		if (empty($Model->data[$Model->alias])) {
			$this->Model->invalidate('captcha', __('captchaContentMissing'));

		} elseif (!$this->_validateDummyField($Model->data[$Model->alias])) {
			$this->Model->invalidate('captcha', __('captchaIllegalContent'));

		} elseif (!$this->_validateCaptchaMinTime($Model->data[$Model->alias])) {
			$this->Model->invalidate('captcha', __('captchaResultTooFast'));

		} elseif (!$this->_validateCaptchaMaxTime($Model->data[$Model->alias])) {
			$this->Model->invalidate('captcha', __('captchaResultTooLate'));

		} elseif (in_array($this->settings[$Model->alias]['type'], array('active', 'both')) && !$this->_validateCaptcha($Model->data[$Model->alias])) {
			$this->Model->invalidate('captcha', __('captchaResultIncorrect'));

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
		if ($this->settings[$this->Model->alias]['dummyField']) {
			$list[] = $this->settings[$this->Model->alias]['dummyField'];
		}
		return $list;
	}


	protected function _validateDummyField($data) {
		$dummyField = $this->settings[$this->Model->alias]['dummyField'];
		if (!isset($data[$dummyField])) {
			return $this->_setError(__('Illegal call'));
		}
		if (!empty($data[$dummyField])) {
			# dummy field not empty - SPAM!
			return $this->_setError(__('Illegal content'), 'DummyField = \''.$data[$dummyField].'\'');
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
		if (isset($data['captcha_hash']) && isset($data['captcha_time'])) {
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
		if (isset($data['captcha_hash']) && isset($data['captcha_time'])) {
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
			return $this->_setError(__('captchaContentMissing'));
		}

		$hash = $this->_buildHash($data);

		if ($data['captcha_hash'] == $hash) {
			return true;
		}
		# wrong captcha content or session expired
		return $this->_setError(__('Captcha incorrect'), 'SubmittedResult = \''.$data['captcha'].'\'');
	}

	/**
	 * return error message (or empty string if none)
	 * @return string
	 */
	public function errors() {
		return $this->error;
	}

	/**
	 * only necessary if there is more than one request per model
	 * 2009-12-18 ms
	 */
	public function reset() {
		$this->error = '';
	}

	/**
	 * build and log error message
	 * 2009-12-18 ms
	 */
	protected function _setError($msg = null, $internalMsg = null) {
		if (!empty($msg)) {
			$this->error = $msg;
		}
		if (!empty($internalMsg)) {
			$this->internalError = $internalMsg;
		}


		$this->_logAttempt();
		return false;
	}

	protected function _buildHash($data) {
		return CaptchaLib::buildHash($data, $this->settings[$this->Model->alias]);
	}

	/**
	 * logs attempts
	 * @param bool errorsOnly (only if error occured, otherwise always)
	 * @returns null if not logged, true otherwise
	 * 2009-12-18 ms
	 */
	protected function _logAttempt($errorsOnly = true) {
		if ($errorsOnly === true && empty($this->error) && empty($this->internalError)) {
			return null;
		}
		if (!$this->settings[$this->Model->alias]['log']) {
			return null;
		}

		//App::import('Component', 'RequestHandler');
		$msg = 'IP \''.CakeRequest::clientIP().'\', Agent \''.env('HTTP_USER_AGENT').'\', Referer \''.env('HTTP_REFERER').'\', Host-Referer \''.CommonComponent::getReferer().'\'';
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


