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
	public $fields = array(
		'id' => array('type' => 'integer', 'key' => 'primary'),
		'article_id' => array('type' => 'integer'),
		'title' => array('type' => 'string', 'null' => false),
		'deleted' => array('type' => 'boolean', 'null' => false, 'default' => '0'),
		'deleted_date' => 'datetime',
		'created' => 'datetime',
		'updated' => 'datetime');

	/**
	 * Records property
	 *
	 * @var array
	 */
	public $records = array(
		array(
			'id' => 1,
			'article_id' => 1,
			'title' => 'First Post',
			'deleted' => 0,
			'deleted_date' => null,
			'created' => '2007-03-18 10:39:23',
			'updated' => '2007-03-18 10:41:31'),
		array(
			'id' => 2,
			'article_id' => 1,
			'title' => 'Second Post',
			'deleted' => 0,
			'deleted_date' => null,
			'created' => '2007-03-18 10:41:23',
			'updated' => '2007-03-18 10:43:31'),
		array(
			'id' => 3,
			'article_id' => 2,
			'title' => 'Third Post',
			'deleted' => 1,
			'deleted_date' => '2008-01-01 00:00:00',
			'created' => '2007-03-18 10:43:23',
			'updated' => '2007-03-18 10:45:31'));

}
