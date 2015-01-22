<?php
class RevisionCommentsRevisionTagFixture extends CakeTestFixture {

	public $fields = [
			'id' => [
					'type' => 'integer',
					'null' => false,
					'default' => null,
					'key' => 'primary'],
			'revision_comment_id' => ['type' => 'integer', 'null' => true],
			'revision_tag_id' => ['type' => 'integer', 'null' => true],
			'indexes' => ['PRIMARY' => ['column' => 'id']]];

	public $records = [
		[
			'id' => 1,
			'revision_comment_id' => 1,
			'revision_tag_id' => 1
		],
		[
			'id' => 2,
			'revision_comment_id' => 1,
			'revision_tag_id' => 2
		],
		[
			'id' => 3,
			'revision_comment_id' => 1,
			'revision_tag_id' => 3
		],
		[
			'id' => 4,
			'revision_comment_id' => 2,
			'revision_tag_id' => 1
		],
		[
			'id' => 5,
			'revision_comment_id' => 2,
			'revision_tag_id' => 3
		],
	];
}
