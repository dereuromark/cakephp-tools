<?php
class RevisionTagFixture extends CakeTestFixture {

	public $fields = [
			'id' => [
					'type' => 'integer',
					'null' => false,
					'default' => null,
					'key' => 'primary'],
			'title' => ['type' => 'string', 'null' => false, 'default' => null],
			'indexes' => ['PRIMARY' => ['column' => 'id']]];

	public $records = [
		[
			'id' => 1,
			'title' => 'Fun',
		],
		[
			'id' => 2,
			'title' => 'Hard'
		],
		[
			'id' => 3,
			'title' => 'Trick'
		],
		[
			'id' => 4,
			'title' => 'News'
		],
	];
}
