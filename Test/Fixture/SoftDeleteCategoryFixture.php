<?php
/**
 * SoftDeleteCategoryFixture
 *
 */
class SoftDeleteCategoryFixture extends CakeTestFixture {

	/**
	 * Fields property
	 *
	 * @var array
	 */
	public $fields = [
		'id' => ['type' => 'integer', 'key' => 'primary'],
		'post_count' => ['type' => 'integer'],
		'deleted_post_count' => ['type' => 'integer'],
		'title' => ['type' => 'string', 'null' => false]];

	/**
	 * Records property
	 *
	 * @var array
	 */
	public $records = [
		[
			'id' => 1,
			'post_count' => 2,
			'deleted_post_count' => 0,
			'title' => 'Category A'],
		[
			'id' => 2,
			'post_count' => 0,
			'deleted_post_count' => 1,
			'title' => 'Category B']];

}
