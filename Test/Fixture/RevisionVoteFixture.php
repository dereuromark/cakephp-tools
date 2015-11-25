<?php

class RevisionVoteFixture extends CakeTestFixture {

	public $fields = [
			'id' => [
					'type' => 'integer',
					'null' => false,
					'default' => null,
					'key' => 'primary'],
			'title' => ['type' => 'string', 'null' => false, 'default' => null],
			'content' => ['type' => 'text', 'null' => false, 'default' => null],
			'revision_comment_id' => ['type' => 'integer', 'null' => true],
			'indexes' => ['PRIMARY' => ['column' => 'id']]];

	public $records = [
		[
			'id' => 1,
			'title' => 'Lorem ipsum dolor sit amet',
			'content' => 'Lorem ipsum dolor sit amet, aliquet feugiat.',
			'revision_comment_id' => 1
		],
		[
			'id' => 2,
			'title' => 'Stuff',
			'content' => 'Lorem ipsum dolor sit.',
			'revision_comment_id' => 1
		],
		[
			'id' => 3,
			'title' => 'Stuff',
			'content' => 'Lorem ipsum dolor sit.',
			'revision_comment_id' => 2
		],
	];
}
