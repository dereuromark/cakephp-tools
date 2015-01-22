<?php

class LogableLogFixture extends CakeTestFixture {

	public $fields = [
		'id' => ['type' => 'integer', 'key' => 'primary'],
		'title' => [
			'type' => 'string',
			'length' => 255,
			'null' => false],
		'description' => [
			'type' => 'string',
			'length' => 255,
			'null' => false],
		'model' => [
			'type' => 'string',
			'length' => 255,
			'null' => false],
		'foreign_id' => ['type' => 'integer', 'null' => true],
		'action' => [
			'type' => 'string',
			'length' => 25,
			'null' => false],
		'user_id' => ['type' => 'integer', 'null' => true],
		'change' => [
			'type' => 'string',
			'length' => 255,
			'null' => false],
		'created' => ['type' => 'datetime', 'null' => true, 'default' => null, 'collate' => null, 'comment' => ''],
		'modified' => ['type' => 'datetime', 'null' => true, 'default' => null, 'collate' => null, 'comment' => ''],
		'indexes' => ['PRIMARY' => ['column' => 'id', 'unique' => 1]],
		'tableParameters' => []
	];

	public $records = [
		[
			'id' => 1,
			'title' => 'Fifth Book',
			'description' => 'LogableBook "Fifth Book" (6) created by LogableUser "Alexander" (66).',
			'model' => 'LogableBook',
			'foreign_id' => 6,
			'action' => 'add',
			'user_id' => 66,
			'change' => 'title'],
		[
			'id' => 2,
			'title' => 'Fifth Book',
			'description' => 'LogableBook "Fifth Book" (6) updated by LogableUser "Alexander" (66).',
			'model' => 'LogableBook',
			'foreign_id' => 6,
			'action' => 'edit',
			'user_id' => 66,
			'change' => 'title'],
		[
			'id' => 3,
			'title' => 'Steven',
			'description' => 'User "Steven" (301) updated by LogableUser "Steven" (301).',
			'model' => 'LogableUser',
			'foreign_id' => 301,
			'action' => 'edit',
			'user_id' => 301,
			'change' => 'name'],
		[
			'id' => 4,
			'title' => 'Fifth Book',
			'description' => 'LogableBook "Fifth Book" (6) deleted by LogableUser "Alexander" (66).',
			'model' => 'LogableBook',
			'foreign_id' => 6,
			'action' => 'delete',
			'user_id' => 66,
			'change' => ''],
		[
			'id' => 5,
			'title' => 'New Book',
			'description' => 'LogableBook "New Book" (7) added by LogableUser "Steven" (301).',
			'model' => 'LogableBook',
			'foreign_id' => 7,
			'action' => 'add',
			'user_id' => 301,
			'change' => 'title'],
		];
}
