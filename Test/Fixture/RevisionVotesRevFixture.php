<?php
class RevisionVotesRevFixture extends CakeTestFixture {

	public $fields = array(
			'version_id' => array('type' => 'integer', 'null' => false, 'default' => null, 'key' => 'primary'),
			'version_created' => array('type' => 'datetime', 'null' => false, 'default' => null),
			'id' => array(
					'type' => 'integer',
					'null' => false,
					'default' => null),
			'title' => array('type' => 'string', 'null' => false, 'default' => null),
			'content' => array('type' => 'text', 'null' => false, 'default' => null),
			'revision_comment_id' => array('type' => 'integer', 'null' => false));

	public $records = array(
		array(
			'version_id' => 1,
			'version_created' => '2008-12-08 11:38:53',
			'id' => 1,
			'title' => 'Lorem ipsum dolor sit amet',
			'content' => 'Lorem ipsum dolor sit amet, aliquet feugiat.',
			'revision_comment_id' => 1
		),
		array(
			'version_id' => 2,
			'version_created' => '2008-12-08 11:38:53',
			'id' => 2,
			'title' => 'Stuff',
			'content' => 'Lorem ipsum dolor sit.',
			'revision_comment_id' => 1
		),
		array(
			'version_id' => 3,
			'version_created' => '2008-12-08 11:38:53',
			'id' => 3,
			'title' => 'Stuff',
			'content' => 'Lorem ipsum dolor sit.',
			'revision_comment_id' => 2
		),
	);
}
