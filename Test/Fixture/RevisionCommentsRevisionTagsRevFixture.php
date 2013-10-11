<?php
class RevisionCommentsRevisionTagsRevFixture extends CakeTestFixture {

	public $fields = array(
			'version_id' => array('type' => 'integer', 'null' => false, 'default' => null, 'key' => 'primary'),
			'version_created' => array('type' => 'datetime', 'null' => false, 'default' => null),
			'id' => array('type' => 'integer', 'null' => false),
			'revision_comment_id' => array('type' => 'integer', 'null' => false),
			'revision_tag_id' => array('type' => 'integer', 'null' => false),
			'indexes' => array('PRIMARY' => array('column' => 'version_id')));

	public $records = array(
		array(
			'version_id' => 1,
			'version_created' => '2008-12-08 11:38:53',
			'id' => 1,
			'revision_comment_id' => 1,
			'revision_tag_id' => 1
		),
		array(
			'version_id' => 2,
			'version_created' => '2008-12-08 11:38:55',
			'id' => 2,
			'revision_comment_id' => 1,
			'revision_tag_id' => 2
		),
		array(
			'version_id' => 3,
			'version_created' => '2008-12-08 11:38:56',
			'id' => 3,
			'revision_comment_id' => 1,
			'revision_tag_id' => 3
		),
		array(
			'version_id' => 4,
			'version_created' => '2008-12-08 11:38:57',
			'id' => 4,
			'revision_comment_id' => 2,
			'revision_tag_id' => 1
		),
		array(
			'version_id' => 5,
			'version_created' => '2008-12-08 11:38:58',
			'id' => 5,
			'revision_comment_id' => 2,
			'revision_tag_id' => 3
		),
	);
}
