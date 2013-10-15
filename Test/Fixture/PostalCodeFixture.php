<?php
/* PostalCode Fixture generated on: 2011-11-20 21:59:25 : 1321822765 */

/**
 * PostalCodeFixture
 *
 */
class PostalCodeFixture extends CakeTestFixture {

	/**
	 * Fields
	 *
	 * @var array
	 */
	public $fields = array(
		'id' => array('type' => 'string', 'null' => false, 'default' => null, 'length' => 36, 'key' => 'primary', 'collate' => 'utf8_unicode_ci', 'comment' => '', 'charset' => 'utf8'),
		'code' => array('type' => 'string', 'null' => false, 'default' => null, 'length' => 5, 'collate' => 'utf8_unicode_ci', 'comment' => '', 'charset' => 'utf8'),
		'country_id' => array('type' => 'integer', 'null' => false, 'default' => '0', 'length' => 2, 'collate' => null, 'comment' => ''),
		'lat' => array('type' => 'float', 'null' => false, 'default' => '0.0000', 'length' => '9,4', 'collate' => null, 'comment' => ''),
		'lng' => array('type' => 'float', 'null' => false, 'default' => '0.0000', 'length' => '9,4', 'collate' => null, 'comment' => ''),
		'official_address' => array('type' => 'string', 'null' => false, 'default' => null, 'collate' => 'utf8_unicode_ci', 'comment' => '', 'charset' => 'utf8'),
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
	);
}
