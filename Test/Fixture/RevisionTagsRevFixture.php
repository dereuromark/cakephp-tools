<?php

class RevisionTagsRevFixture extends CakeTestFixture {

	public $fields = [
			'version_id' => ['type' => 'integer', 'null' => true, 'default' => null, 'key' => 'primary'],
			'version_created' => ['type' => 'datetime', 'null' => true, 'default' => null],
			'id' => ['type' => 'integer', 'null' => false, 'default' => null],
			'title' => ['type' => 'string', 'null' => false, 'default' => null]
	];

	public $records = [
		[
			'version_id' => 1,
			'version_created' => '2008-12-24 11:00:01',
			'id' => 1,
			'title' => 'Fun'
		],
		[
			'version_id' => 2,
			'version_created' => '2008-12-24 11:00:02',
			'id' => 2,
			'title' => 'Hard'
		],
		[
			'version_id' => 3,
			'version_created' => '2008-12-24 11:00:03',
			'id' => 3,
			'title' => 'Tricks'
		],
		[
			'version_id' => 4,
			'version_created' => '2008-12-24 11:00:04',
			'id' => 3,
			'title' => 'Trick'
		],
		[
			'version_id' => 5,
			'version_created' => '2008-12-24 11:00:22',
			'id' => 4,
			'title' => 'News'
		],
	];
}
