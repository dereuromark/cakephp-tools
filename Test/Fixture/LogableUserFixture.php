<?php
class LogableUserFixture extends CakeTestFixture {

	public $fields = array(
		'id' => array('type' => 'integer', 'key' => 'primary'),
		'name' => array('type' => 'string', 'length' => 255, 'null' => false),
		'counter' => array('type' => 'integer', 'length' => 6, 'null' => false, 'default' => 1)
	);

	public $records = array(
		array('id' => 66, 'name' => 'Alexander', 'counter' => 12),
		array('id' => 301, 'name' => 'Steven', 'counter' => 12),
	);
}
