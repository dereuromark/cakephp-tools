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
	public $fields = [
		'id' => ['type' => 'string', 'null' => false, 'default' => null, 'length' => 36, 'key' => 'primary', 'collate' => 'utf8_unicode_ci', 'comment' => '', 'charset' => 'utf8'],
		'code' => ['type' => 'string', 'null' => false, 'default' => null, 'length' => 5, 'collate' => 'utf8_unicode_ci', 'comment' => '', 'charset' => 'utf8'],
		'country_id' => ['type' => 'integer', 'null' => true, 'default' => null, 'length' => 2, 'collate' => null, 'comment' => ''],
		'lat' => ['type' => 'float', 'null' => false, 'default' => '0.0000', 'length' => '9,4', 'collate' => null, 'comment' => ''],
		'lng' => ['type' => 'float', 'null' => false, 'default' => '0.0000', 'length' => '9,4', 'collate' => null, 'comment' => ''],
		'official_address' => ['type' => 'string', 'null' => false, 'default' => null, 'collate' => 'utf8_unicode_ci', 'comment' => '', 'charset' => 'utf8'],
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
	];
}
