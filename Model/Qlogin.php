<?php
//TODO: later Auth Plugin

App::uses('ToolsAppModel', 'Tools.Model');
App::uses('CakeSession', 'Model/Datasource');

/**
 * Manage Quick Logins
 *
 * TODO: Remove CodeKey BC
 *
 * @author Mark Scherer
 * @cakephp 2.x
 * @license MIT
 */
class Qlogin extends ToolsAppModel {

	public $useTable = false;

	public $generator = 'Token'; // TODO: switch to Token ASAP, then remove this

	public $validate = array(
		'url' => array(
			'notEmpty' => array(
				'rule' => array('notEmpty'),
				'message' => 'valErrMandatoryField',
				'last' => true
			),
			'validateUrl' => array(
				'rule' => array('validateUrl', array('deep' => false, 'sameDomain' => true, 'autoComplete' => true)),
				'message' => 'valErrInvalidQloginUrl',
				'last' => true
			)
		),
		'user_id' => array(
			'notEmpty' => array(
				'rule' => array('notEmpty'),
				'message' => 'valErrMandatoryField',
				'last' => true
			),
		),
	);

	public function __construct($id = false, $table = null, $ds = null) {
		if ($generator = Configure::read('Qlogin.generator')) {
			$this->generator = $generator;
		}
		parent::__construct($id, $table, $ds);
	}

	/**
	 * Qlogin::_useKey()
	 *
	 * @param mixed $key
	 * @return boolean Success
	 */
	protected function _useKey($key) {
		if (!isset($this->{$this->generator})) {
			$this->{$this->generator} = ClassRegistry::init('Tools.' . $this->generator);
		}
		return $this->{$this->generator}->useKey('qlogin', $key);
	}

	/**
	 * Qlogin::_newKey()
	 *
	 * @param mixed $uid
	 * @param mixed $content
	 * @return string $key
	 */
	protected function _newKey($uid, $content) {
		if (!isset($this->{$this->generator})) {
			$this->{$this->generator} = ClassRegistry::init('Tools.' . $this->generator);
		}
		return $this->{$this->generator}->newKey('qlogin', null, $uid, $content);
	}

	/**
	 * Qlogin::translate()
	 *
	 * @param mixed $key
	 * @return array
	 */
	public function translate($key) {
		$res = $this->_useKey($key);
		if (!$res) {
			return false;
		}
		$res[$this->generator]['content'] = unserialize($res[$this->generator]['content']);
		$res[$this->generator]['url'] = Router::url($res[$this->generator]['content'], true);
		return $res;
	}

	/**
	 * Generates a qlogin key
	 *
	 * @param mixed $url
	 * @param string $uid
	 * @return string key
	 */
	public function generate($url, $uid) {
		$content = serialize($url);
		return $this->_newKey($uid, $content);
	}

	/**
	 * Qlogin::urlByKey()
	 *
	 * @param string $key
	 * @return string URL (absolute)
	 */
	public static function urlByKey($key) {
		return Router::url(array('admin' => false, 'plugin' => 'tools', 'controller' => 'qlogin', 'action' => 'go', $key), true);
	}

	/**
	 * Makes an absolute url string ready to input anywhere
	 * uses generate() internally to get the key
	 *
	 * @param mixed $url
	 * @param midex $uid (optional)
	 * @return string URL (absolute)
	 */
	public function url($url, $uid = null) {
		if ($uid === null) {
			$uid = CakeSession::read('Auth.User.id');
		}
		$key = $this->generate($url, $uid);
		return $this->urlByKey($key);
	}

}
