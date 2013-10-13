<?php
class RevisionPostFixture extends CakeTestFixture {

	public $fields = array(
			'id' => array(
					'type' => 'integer',
					'null' => false,
					'default' => null,
					'key' => 'primary'),
			'title' => array('type' => 'string', 'null' => false, 'default' => null),
			'content' => array('type' => 'text', 'null' => false, 'default' => null),
			'indexes' => array('PRIMARY' => array('column' => 'id')));

	public $records = array(
		array(
			'id' => 1,
			'title' => 'Lorem ipsum dolor sit amet',
			'content' => 'Lorem ipsum dolor sit amet, aliquet feugiat.'
		),
		array(
			'id' => 2,
			'title' => 'Post 2',
			'content' => 'Lorem ipsum dolor sit.'
		),
		array(
			'id' => 3,
			'title' => 'Post 3',
			'content' => 'Lorem ipsum dolor sit.',
		),
	);
}
