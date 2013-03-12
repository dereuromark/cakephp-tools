<?php
class RevisionArticleFixture extends CakeTestFixture {

	public $fields = array(
			'id' => array(
					'type' => 'integer',
					'null' => false,
					'default' => NULL,
					'key' => 'primary'),
			'user_id' => array('type' => 'integer', 'null' => false, 'default' => NULL),
			'parent_id' => array('type' => 'integer','null' => true,'default' => NULL),
			'lft' => array('type' => 'integer','null' => true,'default' => NULL),
			'rght' => array('type' => 'integer','null' => true,'default' => NULL),
			'title' => array('type' => 'string', 'null' => false, 'default' => NULL),
			'content' => array('type' => 'text', 'null' => false, 'default' => NULL),
			'indexes' => array('PRIMARY' => array('column' => 'id')));
	public $records = array(
		array(
			'id' => 1,
			'user_id' => 1,
			'parent_id' => null,
			'lft' => 1,
			'rght' => 6,
			'title' => 'Lorem ipsum dolor sit amet',
			'content' => 'Lorem ipsum dolor sit amet, aliquet feugiat.'
		),
		array(
			'id' => 2,
			'user_id' => 1,
			'parent_id' => 1,
			'lft' => 2,
			'rght' => 3,
			'title' => 'Lorem ipsum',
			'content' => 'Lorem ipsum dolor sit amet, aliquet feugiat.'
		),
		array(
			'id' => 3,
			'user_id' => 1,
			'parent_id' => 1,
			'lft' => 4,
			'rght' => 5,
			'title' => 'Lorem ipsum',
			'content' => 'Lorem ipsum dolor sit amet, aliquet feugiat.'
		),
	);
}
