<?php
class RevisionCommentsRevisionTagsRevFixture extends CakeTestFixture {

	public $fields = [
			'version_id' => ['type' => 'integer', 'null' => true, 'default' => null, 'key' => 'primary'],
			'version_created' => ['type' => 'datetime', 'null' => true, 'default' => null],
			'id' => ['type' => 'integer', 'null' => false],
			'revision_comment_id' => ['type' => 'integer', 'null' => true],
			'revision_tag_id' => ['type' => 'integer', 'null' => true],
			'indexes' => ['PRIMARY' => ['column' => 'version_id']]];

	public $records = [
		[
			'version_id' => 1,
			'version_created' => '2008-12-08 11:38:53',
			'id' => 1,
			'revision_comment_id' => 1,
			'revision_tag_id' => 1
		],
		[
			'version_id' => 2,
			'version_created' => '2008-12-08 11:38:55',
			'id' => 2,
			'revision_comment_id' => 1,
			'revision_tag_id' => 2
		],
		[
			'version_id' => 3,
			'version_created' => '2008-12-08 11:38:56',
			'id' => 3,
			'revision_comment_id' => 1,
			'revision_tag_id' => 3
		],
		[
			'version_id' => 4,
			'version_created' => '2008-12-08 11:38:57',
			'id' => 4,
			'revision_comment_id' => 2,
			'revision_tag_id' => 1
		],
		[
			'version_id' => 5,
			'version_created' => '2008-12-08 11:38:58',
			'id' => 5,
			'revision_comment_id' => 2,
			'revision_tag_id' => 3
		],
	];
}
