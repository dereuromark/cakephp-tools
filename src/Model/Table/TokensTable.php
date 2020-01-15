<?php

namespace Tools\Model\Table;

use Cake\Utility\Hash;
use Tools\Utility\Random;

/**
 * A generic model to hold tokens
 *
 * @author Mark Scherer
 * @license http://opensource.org/licenses/mit-license.php MIT
 * @method \Tools\Model\Entity\Token get($primaryKey, $options = [])
 * @method \Tools\Model\Entity\Token newEntity($data = null, array $options = [])
 * @method \Tools\Model\Entity\Token[] newEntities(array $data, array $options = [])
 * @method \Tools\Model\Entity\Token|bool save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \Tools\Model\Entity\Token patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \Tools\Model\Entity\Token[] patchEntities($entities, array $data, array $options = [])
 * @method \Tools\Model\Entity\Token findOrCreate($search, callable $callback = null, $options = [])
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
				'allowEmpty' => true,
			],
		],
		'used' => ['numeric'],
	];

	/**
	 * Stores new key in DB
	 *
	 * Checks if this key is already used (should be unique in table)
	 *
	 * @param string $type Type: necessary
	 * @param string|null $key Key: optional key, otherwise a key will be generated
	 * @param mixed|null $uid Uid: optional (if used, only this user can use this key)
	 * @param string|array|null $content Content: up to 255 characters of content may be added (optional)
	 *
	 * @return string|null Key on success, null otherwise
	 */
	public function newKey($type, $key = null, $uid = null, $content = null): ?string {
		if (!$type) {
			return null;
		}

		if (!$key) {
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
				return null;
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
	 * @return \Tools\Model\Entity\Token|null Content - if successfully used or if already used (used=1), NULL otherwise.
	 */
	public function useKey($type, $key, $uid = null, $treatUsedAsInvalid = false) {
		if (!$type || !$key) {
			return null;
		}
		$options = ['conditions' => [$this->getAlias() . '.key' => $key, $this->getAlias() . '.type' => $type]];
		if ($uid) {
			$options['conditions'][$this->getAlias() . '.user_id'] = $uid;
		}
		/** @var \Tools\Model\Entity\Token|null $tokenEntity */
		$tokenEntity = $this->find('all', $options)->first();
		if (!$tokenEntity) {
			return null;
		}
		if ($uid && !empty($tokenEntity['user_id']) && $tokenEntity['user_id'] != $uid) {
			// return $res; # more secure to fail here if user_id is not provided, but was submitted prev.
			return null;
		}
		// already used?
		if (!empty($tokenEntity['used'])) {
			if ($treatUsedAsInvalid) {
				return null;
			}
			// return true and let the application check what to do then
			return $tokenEntity;
		}
		// actually spend key (set to used)
		if ($this->spendKey($tokenEntity['id'])) {
			return $tokenEntity;
		}
		// no limit? we dont spend key then
		if (!empty($tokenEntity['unlimited'])) {
			return $tokenEntity;
		}
		return null;
	}

	/**
	 * Sets Key to "used" (only once!) - directly by ID
	 *
	 * @param int $id Id of key to spend: necessary
	 * @return bool Success
	 */
	public function spendKey($id) {
		if (!$id) {
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
	 * @return int Rows
	 */
	public function garbageCollector() {
		$conditions = [
			$this->getAlias() . '.created <' => date(FORMAT_DB_DATETIME, time() - $this->validity),
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
		$keys['unused_valid'] = $this->find()->where([$this->getAlias() . '.used' => 0, $this->getAlias() . '.created >=' => date(FORMAT_DB_DATETIME, time() - $this->validity)])->count();
		$keys['used_valid'] = $this->find()->where([$this->getAlias() . '.used' => 1, $this->getAlias() . '.created >=' => date(FORMAT_DB_DATETIME, time() - $this->validity)])->count();

		$keys['unused_invalid'] = $this->find()->where([$this->getAlias() . '.used' => 0, $this->getAlias() . '.created <' => date(FORMAT_DB_DATETIME, time() - $this->validity)])->count();
		$keys['used_invalid'] = $this->find()->where([$this->getAlias() . '.used' => 1, $this->getAlias() . '.created <' => date(FORMAT_DB_DATETIME, time() - $this->validity)])->count();

		$types = $this->find('all', ['conditions' => [], 'fields' => ['DISTINCT type']])->toArray();
		$keys['types'] = !empty($types) ? Hash::extract($types, '{n}.type') : [];
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
		if (!$length) {
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
