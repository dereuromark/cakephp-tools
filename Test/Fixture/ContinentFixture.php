<?php
/* Continent Fixture generated on: 2011-07-15 19:07:38 : 1310752058 */
class ContinentFixture extends CakeTestFixture {

	public $fields = array(
		'id' => array('type' => 'integer', 'null' => false, 'default' => NULL, 'length' => 10, 'key' => 'primary'),
		'name' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 64, 'collate' => 'utf8_unicode_ci', 'charset' => 'utf8'),
		'ori_name' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 64, 'collate' => 'utf8_unicode_ci', 'charset' => 'utf8'),
		'parent_id' => array('type' => 'integer', 'null' => false, 'default' => '0', 'length' => 10),
		'lft' => array('type' => 'integer', 'null' => false, 'default' => '0', 'length' => 10),
		'rgt' => array('type' => 'integer', 'null' => false, 'default' => '0', 'length' => 10),
		'status' => array('type' => 'integer', 'null' => false, 'default' => '0', 'length' => 2),
		'modified' => array('type' => 'datetime', 'null' => false, 'default' => NULL),
		'indexes' => array('PRIMARY' => array('column' => 'id', 'unique' => 1)),
		'tableParameters' => array('charset' => 'utf8', 'collate' => 'utf8_unicode_ci', 'engine' => 'MyISAM')
	);

	public $records = array(
		array(
			'id' => 1,
			'name' => 'Lorem ipsum dolor sit amet',
			'ori_name' => 'Lorem ipsum dolor sit amet',
			'parent_id' => 1,
			'lft' => 1,
			'rgt' => 1,
			'status' => 1,
			'modified' => '2011-07-15 19:47:38'
		),
	);
}
