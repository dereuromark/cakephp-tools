<?php
class RevisionPostFixture extends CakeTestFixture {

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
			'title' => 'Lorem ipsum dolor sit amet',
			'content' => 'Lorem ipsum dolor sit amet, aliquet feugiat.'
		],
		[
			'id' => 2,
			'title' => 'Post 2',
			'content' => 'Lorem ipsum dolor sit.'
		],
		[
			'id' => 3,
			'title' => 'Post 3',
			'content' => 'Lorem ipsum dolor sit.',
		],
	];
}
