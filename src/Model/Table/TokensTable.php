<?php

namespace Tools\Model\Table;

use Cake\Utility\Hash;
use Tools\Model\Table\Table;
use Tools\Utility\Random;

/**
 * A generic model to hold tokens
 *
 * @author Mark Scherer
 * @license http://opensource.org/licenses/mit-license.php MIT
 */
class TokensTable extends Table {

	/**
	 * @var string
	 */
	public $displayField = 'key';

	/**
	 * @var array
	 */
	public $order = ['created' => 'DESC'];

	/**
	 * @var int
	 */
	public $defaultLength = 22;

	/**
	 * @var int
	 */
	public $validity = MONTH;

	/**
	 * @var array
	 */
	public $validate = [
		'type' => [
			'notBlank' => [
				'rule' => 'notBlank',
				'message' => 'valErrMandatoryField',
			],
		],
		'key' => [
			'notBlank' => [
				'rule' => ['notBlank'],
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
				'message' => ['valErrMaxCharacters {0}', 255],
				'allowEmpty' => true
			],
		],
		'used' => ['numeric']
	];

	/**
	 * Stores new key in DB
	 *
	 * @param string $type Type: necessary
	 * @param string|null $key Key: optional key, otherwise a key will be generated
	 * @param mixed|null $uid Uid: optional (if used, only this user can use this key)
	 * @param string|array|null $content Content: up to 255 characters of content may be added (optional)
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

		if (is_array($content)) {
			$content = json_encode($content);
		}

		$data = [
			'type' => $type,
			'user_id' => $uid,
			'content' => (string)$content,
			'key' => $key,
		];

		$entity = $this->newEntity($data);
		$max = 99;
		while (!$this->save($entity)) {
			$entity['key'] = $this->generateKey($keyLength);
			$max--;
			if ($max === 0) {
				return false;
			}
		}

		return $entity['key'];
	}

	/**
	 * UsesKey (only once!) - by KEY
	 *
	 * @param string $type : necessary
	 * @param string $key : necessary
	 * @param mixed|null $uid : needs to be provided if this key has a user_id stored
	 * @param bool $treatUsedAsInvalid
	 * @return array Content - if successfully used or if already used (used=1), FALSE else
	 */
	public function useKey($type, $key, $uid = null, $treatUsedAsInvalid = false) {
		if (empty($type) || empty($key)) {
			return false;
		}
		$options = ['conditions' => [$this->alias() . '.key' => $key, $this->alias() . '.type' => $type]];
		if (!empty($uid)) {
			$options['conditions'][$this->alias() . '.user_id'] = $uid;
		}
		$res = $this->find('first', $options);
		if (empty($res)) {
			return false;
		}
		if (!empty($uid) && !empty($res['user_id']) && $res['user_id'] != $uid) {
			// return $res; # more secure to fail here if user_id is not provided, but was submitted prev.
			return false;
		}
		// already used?
		if (!empty($res['used'])) {
			if ($treatUsedAsInvalid) {
				return false;
			}
			// return true and let the application check what to do then
			return $res;
		}
		// actually spend key (set to used)
		if ($this->spendKey($res['id'])) {
			return $res;
		}
		// no limit? we dont spend key then
		if (!empty($res['unlimited'])) {
			return $res;
		}
		//$this->log('VIOLATION in ' . $this->alias() . ' Model (method useKey)');
		return false;
	}

	/**
	 * Sets Key to "used" (only once!) - directly by ID
	 *
	 * @param int $id Id of key to spend: necessary
	 * @return bool Success
	 */
	public function spendKey($id) {
		if (empty($id)) {
			return false;
		}

		//$expression = new \Cake\Database\Expression\QueryExpression(['used = used + 1', 'modified' => date(FORMAT_DB_DATETIME)]);
		$result = $this->updateAll(
			['used = used + 1', 'modified' => date(FORMAT_DB_DATETIME)],
			['id' => $id]
		);
		if ($result) {
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
			$this->alias() . '.created <' => date(FORMAT_DB_DATETIME, time() - $this->validity),
		];
		return $this->deleteAll($conditions);
	}

	/**
	 * Get admin stats
	 *
	 * @return array
	 */
	public function stats() {
		$keys = [];
		$keys['unused_valid'] = $this->find('count', ['conditions' => [$this->alias() . '.used' => 0, $this->alias() . '.created >=' => date(FORMAT_DB_DATETIME, time() - $this->validity)]]);
		$keys['used_valid'] = $this->find('count', ['conditions' => [$this->alias() . '.used' => 1, $this->alias() . '.created >=' => date(FORMAT_DB_DATETIME, time() - $this->validity)]]);

		$keys['unused_invalid'] = $this->find('count', ['conditions' => [$this->alias() . '.used' => 0, $this->alias() . '.created <' => date(FORMAT_DB_DATETIME, time() - $this->validity)]]);
		$keys['used_invalid'] = $this->find('count', ['conditions' => [$this->alias() . '.used' => 1, $this->alias() . '.created <' => date(FORMAT_DB_DATETIME, time() - $this->validity)]]);

		$types = $this->find('all', ['conditions' => [], 'fields' => ['DISTINCT type']]);
		$keys['types'] = !empty($types) ? Hash::extract('{n}.type', $types) : [];
		return $keys;
	}

	/**
	 * Generator of secure random tokens.
	 *
	 * Note that it is best to use an even number for the length.
	 *
	 * @param int|null $length (defaults to defaultLength)
	 * @return string Key
	 */
	public function generateKey($length = null) {
		if (empty($length)) {
			$length = $this->defaultLength;
		}

		if (version_compare(PHP_VERSION, '7.0.0') >= 0) {
			$function = 'random_bytes';
		} elseif (extension_loaded('openssl')) {
			$function = 'openssl_random_pseudo_bytes';
		} else {
			trigger_error('Not secure', E_USER_DEPRECATED);
			return Random::pwd($length);
		}

		$value = bin2hex($function($length / 2));
		if (strlen($value) !== $length) {
			$value = str_pad($value, $length, '0');
		}
		return $value;
	}

}
