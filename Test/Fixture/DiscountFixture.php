<?php
/* Discount Fixture generated on: 2011-11-20 21:59:01 : 1321822741 */

/**
 * DiscountFixture
 *
 */
class DiscountFixture extends CakeTestFixture {

	/**
	 * Fields
	 *
	 * @var array
	 */
	public $fields = [
		'id' => ['type' => 'integer', 'null' => false, 'default' => null, 'length' => 10, 'key' => 'primary', 'collate' => null, 'comment' => ''],
		'name' => ['type' => 'string', 'null' => false, 'default' => null, 'length' => 30, 'collate' => 'utf8_unicode_ci', 'comment' => '', 'charset' => 'utf8'],
		'factor' => ['type' => 'integer', 'null' => false, 'default' => '0', 'length' => 2, 'collate' => null, 'comment' => 'percent'],
		'amount' => ['type' => 'float', 'null' => false, 'default' => '0.00', 'length' => '6,2', 'collate' => null, 'comment' => ''],
		'unlimited' => ['type' => 'boolean', 'null' => false, 'default' => '0', 'collate' => null, 'comment' => ''],
		'min' => ['type' => 'integer', 'null' => false, 'default' => '0', 'collate' => null, 'comment' => ''],
		'valid_from' => ['type' => 'datetime', 'null' => true, 'default' => null, 'collate' => null, 'comment' => ''],
		'valid_until' => ['type' => 'datetime', 'null' => true, 'default' => null, 'collate' => null, 'comment' => ''],
		'model' => ['type' => 'string', 'null' => false, 'default' => null, 'length' => 20, 'collate' => 'utf8_unicode_ci', 'comment' => '', 'charset' => 'utf8'],
		'foreign_id' => ['type' => 'string', 'null' => true, 'default' => null, 'length' => 36, 'collate' => 'utf8_unicode_ci', 'comment' => '', 'charset' => 'utf8'],
		'details' => ['type' => 'text', 'null' => false, 'default' => null, 'collate' => 'utf8_unicode_ci', 'comment' => 'json encoded!', 'charset' => 'utf8'],
		'created' => ['type' => 'datetime', 'null' => true, 'default' => null, 'collate' => null, 'comment' => ''],
		'modified' => ['type' => 'datetime', 'null' => true, 'default' => null, 'collate' => null, 'comment' => ''],
		'indexes' => ['PRIMARY' => ['column' => 'id', 'unique' => 1]],
		'tableParameters' => []
	];

	/**
	 * Records
	 *
	 * @var array
	 */
	public $records = [
		[
			'id' => '1',
			'name' => 'Prozentual',
			'factor' => '2',
			'amount' => '1.00',
			'unlimited' => 0,
			'min' => '10',
			'valid_from' => null,
			'valid_until' => null,
			'model' => '',
			'foreign_id' => '',
			'details' => '[]',
			'created' => '2011-11-10 17:59:01',
			'modified' => '2011-11-17 11:56:30'
		],
	];
}
