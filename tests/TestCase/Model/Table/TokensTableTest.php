<?php

namespace Tools\Test\TestCase\Model\Table;

use RuntimeException;
use Shim\TestSuite\TestCase;
use Tools\Model\Table\TokensTable;

class TokensTableTest extends TestCase {

	/**
	 * @var array<string>
	 */
	protected array $fixtures = [
		'plugin.Tools.Tokens',
	];

	/**
	 * @var \Tools\Model\Table\TokensTable
	 */
	protected $Tokens;

	/**
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		$table = $this->getTableLocator()->get('Tools.Tokens');
		if (!$table instanceof TokensTable) {
			throw new RuntimeException('Unexpected table class');
		}

		$this->Tokens = $table;
	}

	/**
	 * @return void
	 */
	public function tearDown(): void {
		$this->getTableLocator()->clear();

		parent::tearDown();
	}

	/**
	 * @return void
	 */
	public function testTokenInstance() {
		$this->assertInstanceOf(TokensTable::class, $this->Tokens);
	}

	/**
	 * @return void
	 */
	public function testGenerateKey() {
		$key = $this->Tokens->generateKey(4);
		$this->assertTrue(!empty($key) && strlen($key) === 4);
	}

	/**
	 * @return void
	 */
	public function testNewKeySpendKey() {
		$key = $this->Tokens->newKey('test', null, null, 'xyz');
		$this->assertTrue(!empty($key));

		$res = $this->Tokens->useKey('test', $key);
		$this->assertTrue(!empty($res));

		$res = $this->Tokens->useKey('test', $key);
		$this->assertTrue(!empty($res) && !empty($res->used));

		$res = $this->Tokens->useKey('test', $key . 'x');
		$this->assertNull($res);

		$res = $this->Tokens->useKey('testx', $key);
		$this->assertNull($res);
	}

	/**
	 * @return void
	 */
	public function testNewKeyStoresConfiguredTypeValidity() {
		$this->Tokens->typeValidity = [
			'login_link' => DAY,
		];

		$key = $this->Tokens->newKey('login_link', null, 1);
		$token = $this->Tokens->find()->where(['token_key' => $key])->firstOrFail();

		$this->assertSame(DAY, $token->validity);
	}

	/**
	 * Tokens older than the configured `$validity` must not be handed out by
	 * `useKey()` even when they have not been spent yet. Previously the only
	 * enforcement was the manual `garbageCollector()`, which left unused keys
	 * indefinitely valid if the cleanup job did not run.
	 *
	 * @return void
	 */
	public function testUseKeyRejectsExpiredTokens() {
		// Insert a token whose `created` timestamp is older than the default
		// validity window (WEEK). Write raw to the table to bypass the
		// Timestamp behavior which would otherwise overwrite `created`.
		$this->Tokens->getConnection()->insert('tokens', [
			'user_id' => null,
			'type' => 'expired',
			'token_key' => 'expired-key',
			'content' => '',
			'validity' => null,
			'used' => 0,
			'unlimited' => 0,
			'created' => date(FORMAT_DB_DATETIME, time() - 2 * WEEK),
			'modified' => date(FORMAT_DB_DATETIME, time() - 2 * WEEK),
		]);

		$result = $this->Tokens->useKey('expired', 'expired-key');
		$this->assertNull($result);
	}

	/**
	 * Unlimited tokens are exempt from the validity window because they are
	 * intentionally long-lived (e.g. API keys).
	 *
	 * @return void
	 */
	public function testUseKeyAllowsExpiredUnlimitedTokens() {
		$this->Tokens->getConnection()->insert('tokens', [
			'user_id' => null,
			'type' => 'api',
			'token_key' => 'ancient-api-key',
			'content' => '',
			'validity' => DAY,
			'used' => 0,
			'unlimited' => 1,
			'created' => date(FORMAT_DB_DATETIME, time() - 2 * WEEK),
			'modified' => date(FORMAT_DB_DATETIME, time() - 2 * WEEK),
		]);

		$result = $this->Tokens->useKey('api', 'ancient-api-key');
		$this->assertNotNull($result);
	}

	/**
	 * @return void
	 */
	public function testUseKeyRejectsExpiredTokensWithStoredPerTypeValidity() {
		$this->Tokens->getConnection()->insert('tokens', [
			'user_id' => 1,
			'type' => 'login_link',
			'token_key' => 'short-lived-key',
			'content' => '',
			'validity' => DAY,
			'used' => 0,
			'unlimited' => 0,
			'created' => date(FORMAT_DB_DATETIME, time() - 2 * DAY),
			'modified' => date(FORMAT_DB_DATETIME, time() - 2 * DAY),
		]);

		$this->Tokens->typeValidity = [];
		$result = $this->Tokens->useKey('login_link', 'short-lived-key', 1);

		$this->assertNull($result);
	}

	/**
	 * @return void
	 */
	public function testGarbageCollector() {
		$this->Tokens->truncate();

		$this->Tokens->getConnection()->insert('tokens', [
			'user_id' => null,
			'type' => 'default-expired',
			'token_key' => 'default-expired-key',
			'content' => '',
			'validity' => null,
			'used' => 0,
			'unlimited' => 0,
			'created' => date(FORMAT_DB_DATETIME, time() - 2 * WEEK),
			'modified' => date(FORMAT_DB_DATETIME, time() - 2 * WEEK),
		]);
		$this->Tokens->getConnection()->insert('tokens', [
			'user_id' => null,
			'type' => 'custom-valid',
			'token_key' => 'custom-valid-key',
			'content' => '',
			'validity' => 3 * WEEK,
			'used' => 0,
			'unlimited' => 0,
			'created' => date(FORMAT_DB_DATETIME, time() - 2 * WEEK),
			'modified' => date(FORMAT_DB_DATETIME, time() - 2 * WEEK),
		]);
		$this->Tokens->getConnection()->insert('tokens', [
			'user_id' => null,
			'type' => 'api',
			'token_key' => 'api-unlimited-key',
			'content' => '',
			'validity' => DAY,
			'used' => 0,
			'unlimited' => 1,
			'created' => date(FORMAT_DB_DATETIME, time() - 2 * WEEK),
			'modified' => date(FORMAT_DB_DATETIME, time() - 2 * WEEK),
		]);

		$rows = $this->Tokens->garbageCollector();

		$this->assertSame(1, $rows);
		$this->assertSame(0, $this->Tokens->find()->where(['token_key' => 'default-expired-key'])->count());
		$this->assertSame(1, $this->Tokens->find()->where(['token_key' => 'custom-valid-key'])->count());
		$this->assertSame(1, $this->Tokens->find()->where(['token_key' => 'api-unlimited-key'])->count());
	}

	/**
	 * @return void
	 */
	public function testStatsUseStoredValidity() {
		$this->Tokens->truncate();

		$this->Tokens->getConnection()->insert('tokens', [
			'user_id' => null,
			'type' => 'short',
			'token_key' => 'short-invalid-key',
			'content' => '',
			'validity' => DAY,
			'used' => 0,
			'unlimited' => 0,
			'created' => date(FORMAT_DB_DATETIME, time() - 2 * DAY),
			'modified' => date(FORMAT_DB_DATETIME, time() - 2 * DAY),
		]);
		$this->Tokens->getConnection()->insert('tokens', [
			'user_id' => null,
			'type' => 'long',
			'token_key' => 'long-valid-key',
			'content' => '',
			'validity' => 3 * WEEK,
			'used' => 1,
			'unlimited' => 0,
			'created' => date(FORMAT_DB_DATETIME, time() - 2 * WEEK),
			'modified' => date(FORMAT_DB_DATETIME, time() - 2 * WEEK),
		]);

		$stats = $this->Tokens->stats();

		$this->assertGreaterThanOrEqual(1, $stats['unused_invalid']);
		$this->assertGreaterThanOrEqual(1, $stats['used_valid']);
	}

}
