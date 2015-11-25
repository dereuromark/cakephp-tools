<?php

class RevisionCommentFixture extends CakeTestFixture {

	public $fields = [
			'id' => [
					'type' => 'integer',
					'null' => false,
					'default' => null,
					'key' => 'primary'],
			'title' => ['type' => 'string', 'null' => false, 'default' => null],
			'content' => ['type' => 'text', 'null' => false, 'default' => null],
			'indexes' => ['PRIMARY' => ['column' => 'id']]];

	public $records = [
		[
			'id' => 1,
			'title' => 'Comment 1',
			'content' => 'Lorem ipsum dolor sit amet, aliquet feugiat.',
		],
		[
			'id' => 2,
			'title' => 'Comment 2',
			'content' => 'Lorem ipsum dolor sit.',
		],
		[
			'id' => 3,
			'title' => 'Comment 3',
			'content' => 'Lorem ipsum dolor sit.',
		],
	];
}
