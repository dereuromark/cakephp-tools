<?php
/**
 * Short description for class.
 *
 */
class SoftDeletePostFixture extends CakeTestFixture {

	/**
	 * Fields property
	 *
	 * @var array
	 */
	public $fields = [
		'id' => ['type' => 'integer', 'key' => 'primary'],
		'category_id' => ['type' => 'integer'],
		'user_id' => ['type' => 'integer'],
		'title' => ['type' => 'string', 'null' => false],
		'deleted' => ['type' => 'boolean', 'null' => false, 'default' => '0'],
		'deleted_date' => 'datetime',
		'created' => 'datetime',
		'updated' => 'datetime'];

	/**
	 * Records property
	 *
	 * @var array
	 */
	public $records = [
		[
			'id' => 1,
			'category_id' => 1,
			'user_id' => 1,
			'title' => 'First Post',
			'deleted' => 0,
			'deleted_date' => null,
			'created' => '2007-03-18 10:39:23',
			'updated' => '2007-03-18 10:41:31'],
		[
			'id' => 2,
			'category_id' => 1,
			'user_id' => 1,
			'title' => 'Second Post',
			'deleted' => 0,
			'deleted_date' => null,
			'created' => '2007-03-18 10:41:23',
			'updated' => '2007-03-18 10:43:31'],
		[
			'id' => 3,
			'category_id' => 2,
			'user_id' => 1,
			'title' => 'Third Post',
			'deleted' => 1,
			'deleted_date' => '2008-01-01 00:00:00',
			'created' => '2007-03-18 10:43:23',
			'updated' => '2007-03-18 10:45:31']];

}
