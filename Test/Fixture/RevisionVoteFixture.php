<?php

class RevisionVoteFixture extends CakeTestFixture {

	public $fields = array(
			'id' => array(
					'type' => 'integer',
					'null' => false,
					'default' => NULL,
					'key' => 'primary'),
			'title' => array('type' => 'string', 'null' => false, 'default' => NULL),
			'content' => array('type' => 'text', 'null' => false, 'default' => NULL),
			'revision_comment_id' => array('type'=>'integer','null'=>false),
			'indexes' => array('PRIMARY' => array('column' => 'id')));

	public $records = array(
		array(
			'id' => 1,
			'title' => 'Lorem ipsum dolor sit amet',
			'content' => 'Lorem ipsum dolor sit amet, aliquet feugiat.',
			'revision_comment_id' => 1
		),
		array(
			'id' => 2,
			'title' => 'Stuff',
			'content' => 'Lorem ipsum dolor sit.',
			'revision_comment_id' => 1
		),
		array(
			'id' => 3,
			'title' => 'Stuff',
			'content' => 'Lorem ipsum dolor sit.',
			'revision_comment_id' => 2
		),
	);
}
