<?php
/* CodeKey Fixture generated on: 2011-11-20 21:58:47 : 1321822727 */

/**
 * CodeKeyFixture
 *
 */
class CodeKeyFixture extends CakeTestFixture {

	/**
	 * Fields
	 *
	 * @var array
	 */
	public $fields = array(
		'id' => array('type' => 'integer', 'null' => false, 'default' => null, 'length' => 10, 'key' => 'primary', 'collate' => null, 'comment' => ''),
		'user_id' => array('type' => 'string', 'null' => false, 'length' => 36, 'collate' => 'utf8_unicode_ci', 'comment' => '', 'charset' => 'utf8'),
		'type' => array('type' => 'string', 'null' => false, 'length' => 10, 'collate' => 'utf8_unicode_ci', 'comment' => 'e.g.:activate,reactivate', 'charset' => 'utf8'),
		'key' => array('type' => 'string', 'null' => false, 'length' => 60, 'collate' => 'utf8_unicode_ci', 'comment' => '', 'charset' => 'utf8'),
		'content' => array('type' => 'string', 'null' => false, 'collate' => 'utf8_unicode_ci', 'comment' => 'can transport some information', 'charset' => 'utf8'),
		'used' => array('type' => 'boolean', 'null' => false, 'default' => '0', 'collate' => null, 'comment' => ''),
		'created' => array('type' => 'datetime', 'null' => false, 'default' => null, 'collate' => null, 'comment' => ''),
		'modified' => array('type' => 'datetime', 'null' => false, 'default' => null, 'collate' => null, 'comment' => ''),
		'indexes' => array('PRIMARY' => array('column' => 'id', 'unique' => 1)),
		'tableParameters' => array()
	);

	/**
	 * Records
	 *
	 * @var array
	 */
	public $records = array(
		array(
			'id' => '77',
			'user_id' => '1',
			'type' => 'qlogin',
			'key' => '7k8qdcizigtudvxn2v9zep',
			'content' => 'i:1;',
			'used' => 0,
			'created' => '2011-08-02 18:00:41',
			'modified' => '2011-08-02 18:00:41'
		),
		array(
			'id' => '78',
			'user_id' => '2',
			'type' => 'qlogin',
			'key' => '23e32tpkcmdn8x9j8n0n00',
			'content' => 'i:2;',
			'used' => 0,
			'created' => '2011-08-02 18:00:41',
			'modified' => '2011-08-02 18:00:41'
		),
		array(
			'id' => '79',
			'user_id' => '1',
			'type' => 'qlogin',
			'key' => '3mpzed7eoewsjvyvg4vy35',
			'content' => 'a:3:{s:10:"controller";s:4:"test";s:6:"action";s:3:"foo";i:0;s:3:"bar";}',
			'used' => 1,
			'created' => '2011-08-02 18:00:41',
			'modified' => '2011-08-02 18:00:41'
		),
		array(
			'id' => '80',
			'user_id' => '2',
			'type' => 'qlogin',
			'key' => 'af8ww4y7jxzq5n6npmjpxx',
			'content' => 's:13:"/test/foo/bar";',
			'used' => 1,
			'created' => '2011-08-02 18:00:41',
			'modified' => '2011-08-02 18:00:41'
		),
		array(
			'id' => '81',
			'user_id' => '1',
			'type' => 'qlogin',
			'key' => '2s7i3zjw0rn009j4no552b',
			'content' => 'i:1;',
			'used' => 0,
			'created' => '2011-08-02 18:01:16',
			'modified' => '2011-08-02 18:01:16'
		),
		array(
			'id' => '82',
			'user_id' => '2',
			'type' => 'qlogin',
			'key' => 'tro596dig63cay0ps09vre',
			'content' => 'i:2;',
			'used' => 0,
			'created' => '2011-08-02 18:01:16',
			'modified' => '2011-08-02 18:01:16'
		),
		array(
			'id' => '83',
			'user_id' => '1',
			'type' => 'qlogin',
			'key' => 'penfangwc40x550wwvgfmu',
			'content' => 'a:3:{s:10:"controller";s:4:"test";s:6:"action";s:3:"foo";i:0;s:3:"bar";}',
			'used' => 1,
			'created' => '2011-08-02 18:01:16',
			'modified' => '2011-08-02 18:01:16'
		),
		array(
			'id' => '84',
			'user_id' => '2',
			'type' => 'qlogin',
			'key' => '2y7m5srasm3ozej0izxbhe',
			'content' => 's:13:"/test/foo/bar";',
			'used' => 1,
			'created' => '2011-08-02 18:01:16',
			'modified' => '2011-08-02 18:01:16'
		),
		array(
			'id' => '85',
			'user_id' => '1',
			'type' => 'qlogin',
			'key' => '5c6dp2w54ynxii2xo3c50m',
			'content' => 'i:1;',
			'used' => 0,
			'created' => '2011-08-02 18:01:54',
			'modified' => '2011-08-02 18:01:54'
		),
		array(
			'id' => '86',
			'user_id' => '2',
			'type' => 'qlogin',
			'key' => 'fr6a0d4waue2v6hmqeyek5',
			'content' => 'i:2;',
			'used' => 0,
			'created' => '2011-08-02 18:01:54',
			'modified' => '2011-08-02 18:01:54'
		),
	);
}
