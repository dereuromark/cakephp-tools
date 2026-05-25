<?php

namespace Tools\Model\Table;

use Cake\Datasource\ResultSetInterface;
use Cake\I18n\DateTime;
use Cake\Utility\Hash;
use RuntimeException;
use Tools\Model\Entity\Token as TokenEntity;

/**
 * A generic model to hold tokens
 *
 * Note: This feature requires "quoteIdentifiers" set to true due to the "key" field.
 *
 * @author Mark Scherer
 * @license http://opensource.org/licenses/mit-license.php MIT
 * @method \Tools\Model\Entity\Token get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \Tools\Model\Entity\Token newEntity(array $data, array $options = [])
 * @method array<\Tools\Model\Entity\Token> newEntities(array $data, array $options = [])
 * @method \Tools\Model\Entity\Token|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \Tools\Model\Entity\Token patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\Tools\Model\Entity\Token> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \Tools\Model\Entity\Token findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \Tools\Model\Entity\Token newEmptyEntity()
 * @method \Tools\Model\Entity\Token saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \Cake\Datasource\ResultSetInterface<\Tools\Model\Entity\Token>|false saveMany(iterable $entities, array $options = [])
 * @method \Cake\Datasource\ResultSetInterface<\Tools\Model\Entity\Token> saveManyOrFail(iterable $entities, array $options = [])
 * @method \Cake\Datasource\ResultSetInterface<\Tools\Model\Entity\Token>|false deleteMany(iterable $entities, array $options = [])
 * @method \Cake\Datasource\ResultSetInterface<\Tools\Model\Entity\Token> deleteManyOrFail(iterable $entities, array $options = [])
 */
class TokensTable extends Table {

	/**
	 * @var string
	 */
	public string $displayField = 'token_key';

	/**
	 * @var array<int|string, mixed>
	 */
	public array $order = ['created' => 'DESC'];

	/**
	 * @var int
	 */
	public int $defaultLength = 30;

	/**
	 * @var int
	 */
	public int $validity = WEEK;

	/**
	 * Custom validity windows per token type, persisted onto new rows.
	 *
	 * @var array<string, int>
	 */
	public array $typeValidity = [];

	/**
	 * @var array
	 */
	public array $validate = [
		'type' => [
			'notBlank' => [
				'rule' => 'notBlank',
				'message' => 'valErrMandatoryField',
			],
		],
		'token_key' => [
			'notBlank' => [
				'rule' => ['notBlank'],
				'message' => 'valErrMandatoryField',
				'last' => true,
			],
			'isUnique' => [
				'rule' => ['validateUnique'],
				'message' => 'valErrTokenExists',
				'provider' => 'table',
			],
		],
		'content' => [
			'maxLength' => [
				'rule' => ['maxLength', 255],
				'message' => ['valErrMaxCharacters {0}', 255],
				'allowEmptyString' => true,
			],
		],
		'used' => ['numeric'],
	];

	/**
	 * @param array<string, mixed> $config
	 * @return void
	 */
	public function initialize(array $config): void {
		parent::initialize($config);

		if (isset($config['validity'])) {
			$this->validity = (int)$config['validity'];
		}
		if (isset($config['typeValidity'])) {
			/** @var array<string, int> $typeValidity */
			$typeValidity = $config['typeValidity'];
			$this->typeValidity = $typeValidity;
		}
	}

	/**
	 * Stores new key in DB
	 *
	 * Checks if this key is already used (should be unique in table)
	 *
	 * @param string $type Type: necessary
	 * @param string|null $key Key: optional key, otherwise a key will be generated
	 * @param mixed|null $uid Uid: optional (if used, only this user can use this key)
	 * @param array|string|null $content Content: up to 255 characters of content may be added (optional)
	 * @param int|null $validity Custom validity in seconds for this token row.
	 *  Explicit value wins, then configured per-type validity is stored, otherwise `null`
	 *  is persisted and the table default is only used as a runtime fallback when reading.
	 *
	 * @return string Key
	 */
	public function newKey(string $type, ?string $key = null, $uid = null, $content = null, ?int $validity = null): string {
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
			'token_key' => $key,
			'validity' => $validity ?? $this->getConfiguredValidity($type),
		];

		$entity = $this->newEntity($data);
		$max = 99;
		while (!$this->save($entity)) {
			$entity->token_key = $this->generateKey($keyLength);
			$max--;
			if ($max === 0) {
				throw new RuntimeException('Token storage failed after 99 trials.');
			}
		}

		return $entity->token_key;
	}

	/**
	 * UsesKey (only once!) - by KEY
	 *
	 * @param string $type : necessary
	 * @param string $key : necessary
	 * @param string|int|null $uid : needs to be provided if this key has a user_id stored
	 * @param bool $treatUsedAsInvalid
	 * @return \Tools\Model\Entity\Token|null Content - if successfully used or if already used (used=1), NULL otherwise.
	 */
	public function useKey(string $type, string $key, $uid = null, $treatUsedAsInvalid = false) {
		$options = ['conditions' => [$this->getAlias() . '.token_key' => $key, $this->getAlias() . '.type' => $type]];
		if ($uid) {
			$options['conditions'][$this->getAlias() . '.user_id'] = $uid;
		}
		/** @var \Tools\Model\Entity\Token|null $tokenEntity */
		$tokenEntity = $this->find('all', ...$options)->first();
		if (!$tokenEntity) {
			return null;
		}
		if ($uid && $tokenEntity->user_id && $tokenEntity->user_id != $uid) {
			// return $res; # more secure to fail here if user_id is not provided, but was submitted prev.
			return null;
		}
		// Expired? Tokens older than $this->validity seconds are rejected even if
		// not yet used. Unlimited keys are exempt since they intentionally do not
		// get "spent". garbageCollector() can still remove expired unused rows
		// asynchronously, but useKey() must not hand them out in the meantime.
		if ($this->isExpired($tokenEntity)) {
			return null;
		}
		// already used?
		if ($tokenEntity->used) {
			if ($treatUsedAsInvalid) {
				return null;
			}

			// return true and let the application check what to do then
			return $tokenEntity;
		}
		// actually spend key (set to used)
		if ($this->spendKey($tokenEntity->id)) {
			return $tokenEntity;
		}
		// no limit? we dont spend key then
		if ($tokenEntity->unlimited) {
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
	public function spendKey(int $id): bool {
		//$expression = new \Cake\Database\Expression\QueryExpression(['used = used + 1', 'modified' => date(FORMAT_DB_DATETIME)]);
		$result = $this->updateAll(
			['used = used + 1', 'modified' => date(FORMAT_DB_DATETIME)],
			['id' => $id],
		);

		return (bool)$result;
	}

	/**
	 * Remove old/invalid keys
	 * does not remove recently used ones (for proper feedback)!
	 *
	 * @return int Rows
	 */
	public function garbageCollector(): int {
		$ids = [];
		foreach ($this->findTokenRows(['id', 'created', 'validity', 'unlimited']) as $tokenRow) {
			if (!is_array($tokenRow)) {
				continue;
			}
			if ($this->isExpiredRow($tokenRow)) {
				$ids[] = $tokenRow['id'];
			}
		}
		if (!$ids) {
			return 0;
		}

		return $this->deleteAll(['id IN' => $ids]);
	}

	/**
	 * Get admin stats
	 *
	 * @return array
	 */
	public function stats() {
		$keys = [
			'unused_valid' => 0,
			'used_valid' => 0,
			'unused_invalid' => 0,
			'used_invalid' => 0,
		];
		foreach ($this->findTokenRows(['used', 'created', 'validity', 'unlimited']) as $tokenRow) {
			if (!is_array($tokenRow)) {
				continue;
			}
			$isExpired = $this->isExpiredRow($tokenRow);
			if ($tokenRow['used']) {
				$keys[$isExpired ? 'used_invalid' : 'used_valid']++;

				continue;
			}
			$keys[$isExpired ? 'unused_invalid' : 'unused_valid']++;
		}

		$types = $this->find()
			->select(['type'])
			->distinct(['type'])
			->orderBy(['type' => 'ASC'], true)
			->disableHydration()
			->toArray();
		$keys['types'] = empty($types) ? [] : Hash::extract($types, '{n}.type');

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
	public function generateKey(?int $length = null): string {
		if (!$length) {
			$length = $this->defaultLength;
		}

		/** @var callable $function */
		$function = 'random_bytes';
		$value = bin2hex((string)$function((int)($length / 2)));
		if (strlen($value) !== $length) {
			$value = str_pad($value, $length, (string)random_int(0, 9));
		}

		return $value;
	}

	/**
	 * @param string $type
	 * @return int|null
	 */
	protected function getConfiguredValidity(string $type): ?int {
		if (!array_key_exists($type, $this->typeValidity)) {
			return null;
		}

		return (int)$this->typeValidity[$type];
	}

	/**
	 * @param \Tools\Model\Entity\Token $tokenEntity
	 * @return bool
	 */
	protected function isExpired(TokenEntity $tokenEntity): bool {
		return $this->isExpiredRow([
			'created' => $tokenEntity->created,
			'validity' => $tokenEntity->validity,
			'unlimited' => $tokenEntity->unlimited,
		]);
	}

	/**
	 * @param array<string> $fields
	 * @return \Cake\Datasource\ResultSetInterface
	 */
	protected function findTokenRows(array $fields): ResultSetInterface {
		return $this->find()
			->select($fields)
			->disableHydration()
			->disableBufferedResults()
			->orderBy([], true)
			->all();
	}

	/**
	 * @param array<string, mixed> $tokenRow
	 * @return bool
	 */
	protected function isExpiredRow(array $tokenRow): bool {
		if ($tokenRow['unlimited']) {
			return false;
		}

		$validity = $tokenRow['validity'] ?? $this->validity;
		if ($validity <= 0) {
			return false;
		}

		$createdAt = $tokenRow['created'];
		if ($createdAt instanceof DateTime) {
			$createdTs = (int)$createdAt->toUnixString();
		} else {
			$createdTs = (int)strtotime((string)$createdAt);
		}

		return $createdTs > 0 && $createdTs < time() - $validity;
	}

}
