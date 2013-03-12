<?php
class RevisionUserFixture extends CakeTestFixture {

	public $fields = array(
			'id' => array('type'=>'integer', 'null' => false, 'default' => NULL, 'key' => 'primary'),
			'name' => array('type'=>'string', 'null' => false, 'default' => NULL),
			'username' => array('type'=>'string', 'null' => false, 'default' => NULL),
			'created' => array('type'=>'date', 'null' => false, 'default' => NULL),
			'indexes' => array('PRIMARY' => array('column' => 'id'))
			);
	public $records = array(array(
			'id' => 1,
			'name' => 'Alexander',
			'username' => 'alke',
			'created' => '2008-12-07'
			));
}
