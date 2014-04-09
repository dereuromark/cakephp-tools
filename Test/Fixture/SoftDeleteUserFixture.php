<?php
/**
 * SoftDeleteUserFixture
 *
 */
class SoftDeleteUserFixture extends CakeTestFixture {

	/**
	 * Fields property
	 *
	 * @var array
	 */
	public $fields = array(
		'id' => array('type' => 'integer', 'key' => 'primary'),
		'post_count' => array('type' => 'integer'),
		'name' => array('type' => 'string', 'null' => false));

	/**
	 * Records property
	 *
	 * @var array
	 */
	public $records = array(
		array(
			'id' => 1,
			'post_count' => 2,
			'name' => 'User'));

}
