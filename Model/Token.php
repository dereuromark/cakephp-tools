<?php
App::uses('ToolsAppModel', 'Tools.Model');
App::uses('CommonComponent', 'Tools.Controller/Component');

/**
 * A generic model to hold tokens
 *
 * @author Mark Scherer
 * @cakephp 2.x
 * @license MIT
 * 2011-11-17 ms
 */
class Token extends ToolsAppModel {

	public $displayField = 'key';
	public $order = array('Token.created' => 'DESC');

	protected $defaultLength = 22;
	protected $validity = MONTH;

	public $validate = array(
		'type' => array(
			'notEmpty' => array(
				'rule' => array('notEmpty'),
				'message' => 'valErrMandatoryField',
			),
		),
		'key' => array(
			'notEmpty' => array(
				'rule' => array('notEmpty'),
				'message' => 'valErrMandatoryField',
				'last' => true,
			),
			'isUnique' => array(
				'rule' => array('isUnique'),
				'message' => 'valErrTokenExists',
			),
		),
		'content' => array(
			'maxLength' => array(
				'rule' => array('maxLength', 255),
				'message' => array('valErrMaxCharacters %s', 255),
				'allowEmpty' => true
			),
		),
		'used' => array('numeric')
	);

	//public $types = array('activate');

	/**
	 * stores new key in DB
	 * @param string type: necessary
	 * @param string key: optional key, otherwise a key will be generated
	 * @param mixed user_id: optional (if used, only this user can use this key)
	 * @param string content: up to 255 characters of content may be added (optional)
	 * NOW: checks if this key is already used (should be unique in table)
	 * @return string key on SUCCESS, boolean false otherwise
	 * 2009-05-13 ms
	 */
	public function newKey($type, $key = null, $uid = null, $content = null) {
		if (empty($type)) {		// || !in_array($type, $this->types)
			return false;
		}

		if (empty($key)) {
			$key = $this->generateKey($this->defaultLength);
			$keyLength = $this->defaultLength;
		} else {
			$keyLength = mb_strlen($key);
		}

		$data = array(
			'type' => $type,
			'user_id' => (string)$uid,
			'content' => (string)$content,
			'key' => $key,
		);

		$this->set($data);
		$max = 99;
		while (!$this->validates()) {
			$data['key'] = $this->generateKey($keyLength);
			$this->set($data);
			$max--;
			if ($max === 0) { //die('Exeption in Token');
			 	return false;
			}
		}

		$this->create();
		if ($this->save($data)) {
			return $key;
		}
		return false;
	}

	/**
	 * usesKey (only once!) - by KEY
	 * @param string type: necessary
	 * @param string key: necessary
	 * @param mixed user_id: needs to be provided if this key has a user_id stored
	 * @return ARRAY(content) if successfully used or if already used (used=1), FALSE else
	 * 2009-05-13 ms
	 */
	public function useKey($type, $key, $uid = null, $treatUsedAsInvalid = false) {
		if (empty($type) || empty($key)) {
			return false;
		}
		$conditions = array('conditions'=>array($this->alias.'.key'=>$key, $this->alias.'.type'=>$type));
		if (!empty($uid)) {
			$conditions['conditions'][$this->alias.'.user_id'] = $uid;
		}
		$res = $this->find('first', $conditions);
		if (empty($res)) {
			return false;
		}
		if (!empty($uid) && !empty($res[$this->alias]['user_id']) && $res[$this->alias]['user_id'] != $uid) {
			// return $res; # more secure to fail here if user_id is not provided, but was submitted prev.
			return false;
		}
		# already used?
		if (!empty($res[$this->alias]['used'])) {
			if ($treatUsedAsInvalid) {
				return false;
			}
			# return true and let the application check what to do then
			return $res;
		}
		# actually spend key (set to used)
		if ($this->spendKey($res[$this->alias]['id'])) {
			return $res;
		}
		# no limit? we dont spend key then
		if (!empty($res[$this->alias]['unlimited'])) {
			return $res;
		}
		$this->log('VIOLATION in '.$this->alias.' Model (method useKey)');
		return false;
	}

	/**
	 * sets Key to "used" (only once!) - directly by ID
	 * @param id of key to spend: necessary
	 * @return boolean true on success, false otherwise
	 * 2009-05-13 ms
	 */
	public function spendKey($id = null) {
		if (empty($id)) {
			return false;
		}
		//$this->id = $id;
		if ($this->updateAll(array($this->alias.'.used' => $this->alias.'.used + 1', $this->alias.'.modified'=>'"'.date(FORMAT_DB_DATETIME).'"'), array($this->alias.'.id'=>$id))) {
			return true;
		}
		return false;
	}

	/**
	 * remove old/invalid keys
	 * does not remove recently used ones (for proper feedback)!
	 * @return boolean success
	 * 2010-06-17 ms
	 */
	public function garbigeCollector() {
		$conditions = array(
			$this->alias.'.created <'=>date(FORMAT_DB_DATETIME, time()-$this->validity),
		);
		return $this->deleteAll($conditions, false);
	}


	/**
	 * get admin stats
	 * 2010-10-22 ms
	 */
	public function stats() {
		$keys = array();
		$keys['unused_valid'] = $this->find('count', array('conditions'=>array($this->alias.'.used'=>0, $this->alias.'.created >='=>date(FORMAT_DB_DATETIME, time()-$this->validity))));
		$keys['used_valid'] = $this->find('count', array('conditions'=>array($this->alias.'.used'=>1, $this->alias.'.created >='=>date(FORMAT_DB_DATETIME, time()-$this->validity))));

		$keys['unused_invalid'] = $this->find('count', array('conditions'=>array($this->alias.'.used'=>0, $this->alias.'.created <'=>date(FORMAT_DB_DATETIME, time()-$this->validity))));
		$keys['used_invalid'] = $this->find('count', array('conditions'=>array($this->alias.'.used'=>1, $this->alias.'.created <'=>date(FORMAT_DB_DATETIME, time()-$this->validity))));

		$types = $this->find('all', array('conditions'=>array(), 'fields'=>array('DISTINCT type')));
		$keys['types'] = !empty($types) ? Set::extract('/'.$this->alias.'/type', $types) : array();
		return $keys;
	}


	/**
	 * Generator
	 *
	 * TODO: move functionality into Lib class
	 *
	 * @param length (defaults to defaultLength)
	 * @return string key
	 * 2009-05-13 ms
	 */
	public function generateKey($length = null) {
		if (empty($length)) {
			$length = $this->defaultLength;
		}
		if ((class_exists('CommonComponent') || App::import('Component', 'Common')) && method_exists('CommonComponent', 'generatePassword')) {
			return CommonComponent::generatePassword($length);
		} else {
			return $this->_generateKey($length);
		}
	}

	/**
	 * backup method - only used if no custom function exists
	 * 2010-06-17 ms
	 */
	protected function _generateKey($length = null) {
		$chars = "234567890abcdefghijkmnopqrstuvwxyz"; // ABCDEFGHIJKLMNOPQRSTUVWXYZ
		$i = 0;
		$password = "";
		$max = strlen($chars) - 1;

		while ($i < $length) {
			$password .= $chars[mt_rand(0, $max)];
			$i++;
		}
		return $password;
	}

}

