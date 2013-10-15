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
	public $fields = array(
		'id' => array('type' => 'integer', 'null' => false, 'default' => null, 'length' => 10, 'key' => 'primary', 'collate' => null, 'comment' => ''),
		'name' => array('type' => 'string', 'null' => false, 'default' => null, 'length' => 30, 'collate' => 'utf8_unicode_ci', 'comment' => '', 'charset' => 'utf8'),
		'factor' => array('type' => 'integer', 'null' => false, 'default' => '0', 'length' => 2, 'collate' => null, 'comment' => 'percent'),
		'amount' => array('type' => 'float', 'null' => false, 'default' => '0.00', 'length' => '6,2', 'collate' => null, 'comment' => ''),
		'unlimited' => array('type' => 'boolean', 'null' => false, 'default' => '0', 'collate' => null, 'comment' => ''),
		'min' => array('type' => 'integer', 'null' => false, 'default' => '0', 'collate' => null, 'comment' => ''),
		'valid_from' => array('type' => 'datetime', 'null' => true, 'default' => null, 'collate' => null, 'comment' => ''),
		'valid_until' => array('type' => 'datetime', 'null' => true, 'default' => null, 'collate' => null, 'comment' => ''),
		'model' => array('type' => 'string', 'null' => false, 'default' => null, 'length' => 20, 'collate' => 'utf8_unicode_ci', 'comment' => '', 'charset' => 'utf8'),
		'foreign_id' => array('type' => 'string', 'null' => false, 'default' => null, 'length' => 36, 'collate' => 'utf8_unicode_ci', 'comment' => '', 'charset' => 'utf8'),
		'details' => array('type' => 'text', 'null' => false, 'default' => null, 'collate' => 'utf8_unicode_ci', 'comment' => 'json encoded!', 'charset' => 'utf8'),
		'created' => array('type' => 'datetime', 'null' => false, 'default' => null, 'collate' => null, 'comment' => ''),
		'modified' => array('type' => 'datetime', 'null' => false, 'default' => null, 'collate' => null, 'comment' => ''),
		'indexes' => array('PRIMARY' => array('column' => 'id', 'unique' => 1)),
		'tableParameters' => array()
	);

	/**
	 * Records
	 *
	 * @var array
	 */
	public $records = array(
		array(
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
		),
	);
}
