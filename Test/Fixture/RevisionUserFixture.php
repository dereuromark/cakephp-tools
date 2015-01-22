<?php
class RevisionUserFixture extends CakeTestFixture {

	public $fields = [
			'id' => ['type' => 'integer', 'null' => false, 'default' => null, 'key' => 'primary'],
			'name' => ['type' => 'string', 'null' => false, 'default' => null],
			'username' => ['type' => 'string', 'null' => false, 'default' => null],
			'created' => ['type' => 'date', 'null' => true, 'default' => null],
			'indexes' => ['PRIMARY' => ['column' => 'id']]
			];

	public $records = [[
			'id' => 1,
			'name' => 'Alexander',
			'username' => 'alke',
			'created' => '2008-12-07'
			]];
}
