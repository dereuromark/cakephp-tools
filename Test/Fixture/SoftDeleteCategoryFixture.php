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
	public $fields = array(
		'id' => array('type' => 'integer', 'key' => 'primary'),
		'post_count' => array('type' => 'integer'),
		'deleted_post_count' => array('type' => 'integer'),
		'title' => array('type' => 'string', 'null' => false));

	/**
	 * Records property
	 *
	 * @var array
	 */
	public $records = array(
		array(
			'id' => 1,
			'post_count' => 2,
			'deleted_post_count' => 0,
			'title' => 'Category A'),
		array(
			'id' => 2,
			'post_count' => 0,
			'deleted_post_count' => 1,
			'title' => 'Category B'));

}
