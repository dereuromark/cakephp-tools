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
	public $fields = [
		'id' => ['type' => 'integer', 'key' => 'primary'],
		'post_count' => ['type' => 'integer'],
		'name' => ['type' => 'string', 'null' => false]];

	/**
	 * Records property
	 *
	 * @var array
	 */
	public $records = [
		[
			'id' => 1,
			'post_count' => 2,
			'name' => 'User']];

}
