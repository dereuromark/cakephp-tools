<?php

namespace Tools\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * TokenFixture
 */
class TokensFixture extends TestFixture {

	/**
	 * Fields
	 *
	 * @var array
	 */
	public array $fields = [
		'id' => ['type' => 'integer', 'null' => false, 'default' => null, 'length' => 10],
		'user_id' => ['type' => 'integer', 'null' => true, 'length' => 10, 'comment' => ''],
		'type' => ['type' => 'string', 'null' => true, 'default' => null, 'length' => 20, 'comment' => 'e.g.:activate,reactivate'],
		'token_key' => ['type' => 'string', 'null' => true, 'default' => null, 'length' => 60, 'comment' => ''],
		'content' => ['type' => 'string', 'null' => true, 'length' => 255, 'default' => null, 'comment' => 'can transport some information'],
		'used' => ['type' => 'integer', 'null' => false, 'default' => '0', 'collate' => null, 'comment' => ''],
		'created' => ['type' => 'datetime', 'null' => true, 'default' => null, 'collate' => null, 'comment' => ''],
		'modified' => ['type' => 'datetime', 'null' => true, 'default' => null, 'collate' => null, 'comment' => ''],
		'unlimited' => ['type' => 'boolean', 'null' => false, 'default' => '0', 'comment' => ''],
		'_constraints' => ['primary' => ['type' => 'primary', 'columns' => ['id']]],
	];

	/**
	 * Records
	 *
	 * @var array
	 */
	public array $records = [
		[
			'user_id' => 1,
			'type' => 'qlogin',
			'token_key' => '7k8qdcizigtudvxn2v9zep',
			'content' => 'i:1;',
			'used' => 0,
			'created' => '2011-08-02 18:00:41',
			'modified' => '2011-08-02 18:00:41',
			'unlimited' => false,
		],
		[
			'user_id' => '2',
			'type' => 'qlogin',
			'token_key' => '23e32tpkcmdn8x9j8n0n00',
			'content' => 'i:2;',
			'used' => 0,
			'created' => '2011-08-02 18:00:41',
			'modified' => '2011-08-02 18:00:41',
			'unlimited' => false,
		],
		[
			'user_id' => '1',
			'type' => 'qlogin',
			'token_key' => '3mpzed7eoewsjvyvg4vy35',
			'content' => 'a:3:{s:10:"controller";s:4:"test";s:6:"action";s:3:"foo";i:0;s:3:"bar";}',
			'used' => 1,
			'created' => '2011-08-02 18:00:41',
			'modified' => '2011-08-02 18:00:41',
			'unlimited' => false,
		],
		[
			'user_id' => '2',
			'type' => 'qlogin',
			'token_key' => 'af8ww4y7jxzq5n6npmjpxx',
			'content' => 's:13:"/test/foo/bar";',
			'used' => 1,
			'created' => '2011-08-02 18:00:41',
			'modified' => '2011-08-02 18:00:41',
			'unlimited' => false,
		],
		[
			'user_id' => '1',
			'type' => 'qlogin',
			'token_key' => '2s7i3zjw0rn009j4no552b',
			'content' => 'i:1;',
			'used' => 0,
			'created' => '2011-08-02 18:01:16',
			'modified' => '2011-08-02 18:01:16',
			'unlimited' => false,
		],
		[
			'user_id' => '2',
			'type' => 'qlogin',
			'token_key' => 'tro596dig63cay0ps09vre',
			'content' => 'i:2;',
			'used' => 0,
			'created' => '2011-08-02 18:01:16',
			'modified' => '2011-08-02 18:01:16',
			'unlimited' => false,
		],
		[
			'user_id' => '1',
			'type' => 'qlogin',
			'token_key' => 'penfangwc40x550wwvgfmu',
			'content' => 'a:3:{s:10:"controller";s:4:"test";s:6:"action";s:3:"foo";i:0;s:3:"bar";}',
			'used' => 1,
			'created' => '2011-08-02 18:01:16',
			'modified' => '2011-08-02 18:01:16',
			'unlimited' => false,
		],
		[
			'user_id' => '2',
			'type' => 'qlogin',
			'token_key' => '2y7m5srasm3ozej0izxbhe',
			'content' => 's:13:"/test/foo/bar";',
			'used' => 1,
			'created' => '2011-08-02 18:01:16',
			'modified' => '2011-08-02 18:01:16',
			'unlimited' => false,
		],
		[
			'user_id' => '1',
			'type' => 'qlogin',
			'token_key' => '5c6dp2w54ynxii2xo3c50m',
			'content' => 'i:1;',
			'used' => 0,
			'created' => '2011-08-02 18:01:54',
			'modified' => '2011-08-02 18:01:54',
			'unlimited' => false,
		],
		[
			'user_id' => '2',
			'type' => 'qlogin',
			'token_key' => 'fr6a0d4waue2v6hmqeyek5',
			'content' => 'i:2;',
			'used' => 0,
			'created' => '2011-08-02 18:01:54',
			'modified' => '2011-08-02 18:01:54',
			'unlimited' => false,
		],
	];

}
