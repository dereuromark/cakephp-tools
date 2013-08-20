<?php
/* Smiley Fixture generated on: 2010-06-02 01:06:59 : 1275435239 */
class SmileyFixture extends CakeTestFixture {

	public $fields = array(
		'id' => array('type' => 'integer', 'null' => false, 'default' => NULL, 'length' => 10, 'key' => 'primary'),
		'smiley_cat_id' => array('type' => 'integer', 'null' => false, 'default' => '0', 'length' => 10),
		'smiley_path' => array('type' => 'string', 'null' => false),
		'title' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 32),
		'prim_code' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 15),
		'sec_code' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 15),
		'is_base' => array('type' => 'boolean', 'null' => false, 'default' => '0'),
		'sort' => array('type' => 'integer', 'null' => false, 'default' => '0', 'length' => 10),
		'active' => array('type' => 'boolean', 'null' => false, 'default' => '0'),
		'created' => array('type' => 'datetime', 'null' => false, 'default' => NULL),
		'modified' => array('type' => 'datetime', 'null' => false, 'default' => NULL),
		'indexes' => array('PRIMARY' => array('column' => 'id', 'unique' => 1)),
		'tableParameters' => array()
	);

	public $records = array(
		array(
			'id' => 1,
			'smiley_cat_id' => 1,
			'smiley_path' => 'Lorem ipsum dolor sit amet',
			'title' => 'Lorem ipsum dolor sit amet',
			'prim_code' => 'Lorem ipsum d',
			'sec_code' => 'Lorem ipsum d',
			'is_base' => 1,
			'sort' => 1,
			'active' => 1,
			'created' => '2010-06-02 01:33:59',
			'modified' => '2010-06-02 01:33:59'
		),
	);
}
