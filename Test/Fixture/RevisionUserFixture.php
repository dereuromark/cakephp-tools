<?php
class RevisionUserFixture extends CakeTestFixture {

	public $fields = array(
			'id' => array('type' => 'integer', 'null' => false, 'default' => null, 'key' => 'primary'),
			'name' => array('type' => 'string', 'null' => false, 'default' => null),
			'username' => array('type' => 'string', 'null' => false, 'default' => null),
			'created' => array('type' => 'date', 'null' => false, 'default' => null),
			'indexes' => array('PRIMARY' => array('column' => 'id'))
			);

	public $records = array(array(
			'id' => 1,
			'name' => 'Alexander',
			'username' => 'alke',
			'created' => '2008-12-07'
			));
}
