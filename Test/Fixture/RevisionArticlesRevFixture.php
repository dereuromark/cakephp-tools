<?php
class RevisionArticlesRevFixture extends CakeTestFixture {

	public $fields = [
			'version_id' => ['type' => 'integer', 'null' => true, 'default' => null, 'key' => 'primary'],
			'version_created' => ['type' => 'datetime', 'null' => true, 'default' => null],
			'id' => ['type' => 'integer', 'null' => false, 'default' => null],
			'user_id' => ['type' => 'integer', 'null' => true, 'default' => null],
			'parent_id' => ['type' => 'integer', 'null' => true, 'default' => null],
			'title' => ['type' => 'string', 'null' => false, 'default' => null],
			'content' => ['type' => 'text', 'null' => false, 'default' => null],
			'indexes' => ['PRIMARY' => ['column' => 'version_id']]];

	public $records = [
	];
}
