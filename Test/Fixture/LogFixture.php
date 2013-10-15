<?php

/**
 * LogFixture
 *
 */
class LogFixture extends CakeTestFixture {

	/**
	 * Fields
	 *
	 * @var array
	 */
	public $fields = array(
		'id' => array('type' => 'integer', 'null' => false, 'default' => null, 'length' => 10, 'key' => 'primary', 'collate' => null, 'comment' => ''),
		'title' => array('type' => 'string', 'null' => false, 'default' => null, 'length' => 100, 'collate' => 'utf8_unicode_ci', 'comment' => '', 'charset' => 'utf8'),
		'description' => array('type' => 'string', 'null' => false, 'default' => null, 'collate' => 'utf8_unicode_ci', 'comment' => '', 'charset' => 'utf8'),
		'change' => array('type' => 'text', 'null' => false, 'default' => null, 'collate' => 'utf8_unicode_ci', 'comment' => '', 'charset' => 'utf8'),
		'model' => array('type' => 'string', 'null' => false, 'default' => null, 'length' => 40, 'collate' => 'utf8_unicode_ci', 'comment' => '', 'charset' => 'utf8'),
		'foreign_id' => array('type' => 'string', 'null' => false, 'default' => null, 'length' => 36, 'collate' => 'utf8_unicode_ci', 'comment' => '', 'charset' => 'utf8'),
		'action' => array('type' => 'string', 'null' => false, 'default' => null, 'length' => 40, 'collate' => 'utf8_unicode_ci', 'comment' => '', 'charset' => 'utf8'),
		'user_id' => array('type' => 'string', 'null' => false, 'default' => null, 'length' => 36, 'collate' => 'utf8_unicode_ci', 'comment' => '', 'charset' => 'utf8'),
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
			'id' => '10',
			'title' => '2',
			'description' => 'PrepaidAccount "2" (1) added by User "Admin" (16).',
			'change' => 'amount () => (2), user_id () => (14), created () => (2011-07-30 18:36:55)',
			'model' => 'PrepaidAccount',
			'foreign_id' => '1',
			'action' => 'add',
			'user_id' => '16',
			'created' => '2011-07-30 18:36:55',
			'modified' => '2011-07-30 18:36:55'
		),
		array(
			'id' => '11',
			'title' => '3',
			'description' => 'PrepaidAccount "3" (2) added by User "Admin" (16).',
			'change' => 'amount () => (3), user_id () => (14), created () => (2011-07-30 19:26:31)',
			'model' => 'PrepaidAccount',
			'foreign_id' => '2',
			'action' => 'add',
			'user_id' => '16',
			'created' => '2011-07-30 19:26:31',
			'modified' => '2011-07-30 19:26:31'
		),
		array(
			'id' => '12',
			'title' => '1',
			'description' => 'PrepaidAccount "1" (3) added by User "Admin" (16).',
			'change' => 'amount () => (1), user_id () => (17), created () => (2011-07-30 20:46:18)',
			'model' => 'PrepaidAccount',
			'foreign_id' => '3',
			'action' => 'add',
			'user_id' => '16',
			'created' => '2011-07-30 20:46:18',
			'modified' => '2011-07-30 20:46:18'
		),
		array(
			'id' => '13',
			'title' => '3.0000',
			'description' => 'PrepaidAccount "3.0000" (2) deleted by User "Admin" (16).',
			'change' => '',
			'model' => 'PrepaidAccount',
			'foreign_id' => '2',
			'action' => 'delete',
			'user_id' => '16',
			'created' => '2011-07-30 20:46:21',
			'modified' => '2011-07-30 20:46:21'
		),
		array(
			'id' => '14',
			'title' => '2.50',
			'description' => 'PrepaidAccount "2.50" (4) added by User "admin@admin.de" (16).',
			'change' => 'amount () => (2.50), user_id () => (20), created () => (2011-09-16 10:21:39)',
			'model' => 'PrepaidAccount',
			'foreign_id' => '4',
			'action' => 'add',
			'user_id' => '16',
			'created' => '2011-09-16 10:21:39',
			'modified' => '2011-09-16 10:21:39'
		),
		array(
			'id' => '15',
			'title' => '3.5000',
			'description' => 'PrepaidAccount "3.5000" (4) updated by User "admin@admin.de" (16).',
			'change' => 'amount (2.5000) => (3.5000)',
			'model' => 'PrepaidAccount',
			'foreign_id' => '4',
			'action' => 'edit',
			'user_id' => '16',
			'created' => '2011-09-16 10:22:35',
			'modified' => '2011-09-16 10:22:35'
		),
		array(
			'id' => '16',
			'title' => '4',
			'description' => 'PrepaidAccount "4" (1) updated by User "user@user.de" (14).',
			'change' => 'amount (2.0000) => (4)',
			'model' => 'PrepaidAccount',
			'foreign_id' => '1',
			'action' => 'edit',
			'user_id' => '14',
			'created' => '2011-09-23 17:13:40',
			'modified' => '2011-09-23 17:13:40'
		),
		array(
			'id' => '17',
			'title' => '4.0000',
			'description' => 'Custom action by User "user@user.de" (14).',
			'change' => '',
			'model' => 'PrepaidAccount',
			'foreign_id' => '1',
			'action' => 'deposited (2.00)',
			'user_id' => '14',
			'created' => '2011-09-23 17:13:41',
			'modified' => '2011-09-23 17:13:41'
		),
		array(
			'id' => '18',
			'title' => '19',
			'description' => 'PrepaidAccount "19" (1) updated by User "user@user.de" (14).',
			'change' => 'amount (4.0000) => (19)',
			'model' => 'PrepaidAccount',
			'foreign_id' => '1',
			'action' => 'edit',
			'user_id' => '14',
			'created' => '2011-09-23 17:14:57',
			'modified' => '2011-09-23 17:14:57'
		),
		array(
			'id' => '19',
			'title' => '19.0000',
			'description' => 'Custom action by User "user@user.de" (14).',
			'change' => '',
			'model' => 'PrepaidAccount',
			'foreign_id' => '1',
			'action' => 'deposited (15.00)',
			'user_id' => '14',
			'created' => '2011-09-23 17:14:57',
			'modified' => '2011-09-23 17:14:57'
		),
	);
}
