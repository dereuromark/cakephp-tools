<?php
App::uses('ToolsAppModel', 'Tools.Model');
App::uses('CommonComponent', 'Tools.Controller/Component');
App::uses('Hash', 'Utility');

/**
 * A generic model to hold tokens
 *
 * @author Mark Scherer
 * @license http://opensource.org/licenses/mit-license.php MIT
 */
class Token extends ToolsAppModel {

	public $displayField = 'key';

	public $order = ['Token.created' => 'DESC'];

	public $defaultLength = 22;

	public $validity = MONTH;

	public $validate = [
		'type' => [
			'notEmpty' => [
				'rule' => ['notEmpty'],
				'message' => 'valErrMandatoryField',
			],
		],
		'key' => [
			'notEmpty' => [
				'rule' => ['notEmpty'],
				'message' => 'valErrMandatoryField',
				'last' => true,
			],
			'isUnique' => [
				'rule' => ['isUnique'],
				'message' => 'valErrTokenExists',
			],
		],
		'content' => [
			'maxLength' => [
				'rule' => ['maxLength', 255],
				'message' => ['valErrMaxCharacters %s', 255],
				'allowEmpty' => true
			],
		],
		'used' => ['numeric']
	];

	/**
	 * Stores new key in DB
	 *
	 * @param string type: necessary
	 * @param string key: optional key, otherwise a key will be generated
	 * @param mixed user_id: optional (if used, only this user can use this key)
	 * @param string content: up to 255 characters of content may be added (optional)
	 * NOW: checks if this key is already used (should be unique in table)
	 * @return string key on SUCCESS, boolean false otherwise
	 */
	public function newKey($type, $key = null, $uid = null, $content = null) {
		if (empty($type)) {
			return false;
		}

		if (empty($key)) {
			$key = $this->generateKey($this->defaultLength);
			$keyLength = $this->defaultLength;
		} else {
			$keyLength = mb_strlen($key);
		}

		$data = [
			'type' => $type,
			'user_id' => (string)$uid,
			'content' => (string)$content,
			'key' => $key,
		];

		$this->set($data);
		$max = 99;
		while (!$this->validates()) {
			$data['key'] = $this->generateKey($keyLength);
			$this->set($data);
			$max--;
			if ($max === 0) {
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
	 * UsesKey (only once!) - by KEY
	 *
	 * @param string type: necessary
	 * @param string key: necessary
	 * @param mixed user_id: needs to be provided if this key has a user_id stored
	 * @return array Content - if successfully used or if already used (used=1), FALSE else
	 */
	public function useKey($type, $key, $uid = null, $treatUsedAsInvalid = false) {
		if (empty($type) || empty($key)) {
			return false;
		}
		$options = ['conditions' => [$this->alias . '.key' => $key, $this->alias . '.type' => $type]];
		if (!empty($uid)) {
			$options['conditions'][$this->alias . '.user_id'] = $uid;
		}
		$res = $this->find('first', $options);
		if (empty($res)) {
			return false;
		}
		if (!empty($uid) && !empty($res[$this->alias]['user_id']) && $res[$this->alias]['user_id'] != $uid) {
			// return $res; # more secure to fail here if user_id is not provided, but was submitted prev.
			return false;
		}
		// already used?
		if (!empty($res[$this->alias]['used'])) {
			if ($treatUsedAsInvalid) {
				return false;
			}
			// return true and let the application check what to do then
			return $res;
		}
		// actually spend key (set to used)
		if ($this->spendKey($res[$this->alias]['id'])) {
			return $res;
		}
		// no limit? we dont spend key then
		if (!empty($res[$this->alias]['unlimited'])) {
			return $res;
		}
		$this->log('VIOLATION in ' . $this->alias . ' Model (method useKey)');
		return false;
	}

	/**
	 * Sets Key to "used" (only once!) - directly by ID
	 *
	 * @param id of key to spend: necessary
	 * @return bool Success
	 */
	public function spendKey($id = null) {
		if (empty($id)) {
			return false;
		}
		//$this->id = $id;
		if ($this->updateAll([$this->alias . '.used' => $this->alias . '.used + 1', $this->alias . '.modified' => '"' . date(FORMAT_DB_DATETIME) . '"'], [$this->alias . '.id' => $id])) {
			return true;
		}
		return false;
	}

	/**
	 * Remove old/invalid keys
	 * does not remove recently used ones (for proper feedback)!
	 *
	 * @return bool success
	 */
	public function garbageCollector() {
		$conditions = [
			$this->alias . '.created <' => date(FORMAT_DB_DATETIME, time() - $this->validity),
		];
		return $this->deleteAll($conditions, false);
	}

	/**
	 * Get admin stats
	 */
	public function stats() {
		$keys = [];
		$keys['unused_valid'] = $this->find('count', ['conditions' => [$this->alias . '.used' => 0, $this->alias . '.created >=' => date(FORMAT_DB_DATETIME, time() - $this->validity)]]);
		$keys['used_valid'] = $this->find('count', ['conditions' => [$this->alias . '.used' => 1, $this->alias . '.created >=' => date(FORMAT_DB_DATETIME, time() - $this->validity)]]);

		$keys['unused_invalid'] = $this->find('count', ['conditions' => [$this->alias . '.used' => 0, $this->alias . '.created <' => date(FORMAT_DB_DATETIME, time() - $this->validity)]]);
		$keys['used_invalid'] = $this->find('count', ['conditions' => [$this->alias . '.used' => 1, $this->alias . '.created <' => date(FORMAT_DB_DATETIME, time() - $this->validity)]]);

		$types = $this->find('all', ['conditions' => [], 'fields' => ['DISTINCT type']]);
		$keys['types'] = !empty($types) ? Hash::extract('{n}.' . $this->alias . '.type', $types) : [];
		return $keys;
	}

	/**
	 * Generator
	 *
	 * @param length (defaults to defaultLength)
	 * @return string Key
	 */
	public function generateKey($length = null) {
		if (empty($length)) {
			$length = $this->defaultLength;
		}
		App::uses('RandomLib', 'Tools.Lib');
		return RandomLib::generatePassword($length);
	}

}
