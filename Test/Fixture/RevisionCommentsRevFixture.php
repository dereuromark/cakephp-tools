<?php
class RevisionCommentsRevFixture extends CakeTestFixture {

	public $fields = [
			'version_id' => ['type' => 'integer', 'null' => true, 'default' => null, 'key' => 'primary'],
			'version_created' => ['type' => 'datetime', 'null' => true, 'default' => null],
			'id' => [
					'type' => 'integer',
					'null' => false,
					'default' => null],
			'title' => ['type' => 'string', 'null' => false, 'default' => null],
			'content' => ['type' => 'text', 'null' => false, 'default' => null],
			'Tag' => ['type' => 'string', 'null' => true, 'default' => null]
	];

	public $records = [
		[
			'version_id' => 1,
			'version_created' => '2008-12-08 11:38:53',
			'id' => 1,
			'title' => 'Comment 1',
			'content' => 'Lorem ipsum dolor sit amet, aliquet feugiat.',
			'Tag' => '1,2,3'
		],
		[
			'version_id' => 2,
			'version_created' => '2008-12-08 11:38:54',
			'id' => 2,
			'title' => 'Comment 2',
			'content' => 'Lorem ipsum dolor sit.',
			'Tag' => '1,3'
		],
		[
			'version_id' => 3,
			'version_created' => '2008-12-08 11:38:55',
			'id' => 3,
			'title' => 'Comment 3',
			'content' => 'Lorem ipsum dolor sit.',
			'Tag' => null
		],
	];
}
