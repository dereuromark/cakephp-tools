<?php
class RevisionArticlesRevFixture extends CakeTestFixture {

	public $fields = array(
			'version_id' => array('type' => 'integer', 'null' => false, 'default' => null, 'key' => 'primary'),
			'version_created' => array('type' => 'datetime', 'null' => false, 'default' => null),
			'id' => array('type' => 'integer', 'null' => false, 'default' => null),
			'user_id' => array('type' => 'integer', 'null' => false, 'default' => null),
			'parent_id' => array('type' => 'integer', 'null' => true, 'default' => null),
			'title' => array('type' => 'string', 'null' => false, 'default' => null),
			'content' => array('type' => 'text', 'null' => false, 'default' => null),
			'indexes' => array('PRIMARY' => array('column' => 'version_id')));

	public $records = array(
	);
}
