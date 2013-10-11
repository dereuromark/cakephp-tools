<?php

class ExifFixture extends CakeTestFixture {

	public $fields = array(
		'id' => array('type' => 'integer', 'null' => false, 'default' => null, 'length' => 10, 'key' => 'primary'),
		'user_id' => array('type' => 'string', 'null' => false, 'default' => null, 'length' => 36, 'key' => 'index'),
		'title' => array('type' => 'string', 'null' => false, 'default' => null, 'length' => 30),
		'body' => array('type' => 'string', 'null' => false, 'default' => null, 'length' => 255),
		'created' => array('type' => 'datetime', 'null' => false, 'default' => null),
		'modified' => array('type' => 'datetime', 'null' => false, 'default' => null),
		'indexes' => array('PRIMARY' => array('column' => 'id', 'unique' => 1), 'user_id' => array('column' => 'user_id', 'unique' => 0)),
		'tableParameters' => array()
	);

	public $records = array(
		array(
			'id' => 1,
			'user_id' => 'Lorem ipsum dolor sit amet',
			'title' => 'Lorem ipsum dolor sit amet',
			'body' => 'fdfddf',
			'value' => 1,
			'created' => '2010-05-06 16:14:00',
			'modified' => '2010-05-06 16:14:00'
		),
	);
}
