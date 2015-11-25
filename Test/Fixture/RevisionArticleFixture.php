<?php
class RevisionArticleFixture extends CakeTestFixture {

	public $fields = [
			'id' => [
					'type' => 'integer',
					'null' => false,
					'default' => null,
					'key' => 'primary'],
			'user_id' => ['type' => 'integer', 'null' => true, 'default' => null],
			'parent_id' => ['type' => 'integer', 'null' => true, 'default' => null],
			'lft' => ['type' => 'integer', 'null' => true, 'default' => null],
			'rght' => ['type' => 'integer', 'null' => true, 'default' => null],
			'title' => ['type' => 'string', 'null' => false, 'default' => null],
			'content' => ['type' => 'text', 'null' => false, 'default' => null],
			'indexes' => ['PRIMARY' => ['column' => 'id']]];

	public $records = [
		[
			'id' => 1,
			'user_id' => 1,
			'parent_id' => null,
			'lft' => 1,
			'rght' => 6,
			'title' => 'Lorem ipsum dolor sit amet',
			'content' => 'Lorem ipsum dolor sit amet, aliquet feugiat.'
		],
		[
			'id' => 2,
			'user_id' => 1,
			'parent_id' => 1,
			'lft' => 2,
			'rght' => 3,
			'title' => 'Lorem ipsum',
			'content' => 'Lorem ipsum dolor sit amet, aliquet feugiat.'
		],
		[
			'id' => 3,
			'user_id' => 1,
			'parent_id' => 1,
			'lft' => 4,
			'rght' => 5,
			'title' => 'Lorem ipsum',
			'content' => 'Lorem ipsum dolor sit amet, aliquet feugiat.'
		],
	];
}
