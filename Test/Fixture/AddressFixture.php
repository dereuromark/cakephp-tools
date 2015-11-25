<?php
/* Address Fixture generated on: 2011-11-20 21:58:38 : 1321822718 */

/**
 * AddressFixture
 *
 */
class AddressFixture extends CakeTestFixture {

	/**
	 * Fields
	 *
	 * @var array
	 */
	public $fields = [
		'id' => ['type' => 'integer', 'null' => false, 'default' => null, 'length' => 10, 'key' => 'primary', 'collate' => null, 'comment' => ''],
		'foreign_id' => ['type' => 'string', 'null' => true, 'default' => null, 'length' => 36, 'collate' => 'utf8_unicode_ci', 'comment' => '', 'charset' => 'utf8'],
		'model' => ['type' => 'string', 'null' => false, 'default' => null, 'length' => 30, 'collate' => 'utf8_unicode_ci', 'comment' => '', 'charset' => 'utf8'],
		'country_id' => ['type' => 'integer', 'null' => true, 'default' => null, 'length' => 10, 'collate' => null, 'comment' => 'redundance purposely'],
		'first_name' => ['type' => 'string', 'null' => false, 'default' => null, 'length' => 50, 'collate' => 'utf8_unicode_ci', 'comment' => '', 'charset' => 'utf8'],
		'last_name' => ['type' => 'string', 'null' => false, 'default' => null, 'length' => 50, 'collate' => 'utf8_unicode_ci', 'comment' => '', 'charset' => 'utf8'],
		'street' => ['type' => 'string', 'null' => false, 'default' => null, 'length' => 100, 'collate' => 'utf8_unicode_ci', 'comment' => 'street address and street numbe', 'charset' => 'utf8'],
		'postal_code' => ['type' => 'string', 'null' => false, 'default' => null, 'length' => 10, 'collate' => 'utf8_unicode_ci', 'comment' => '', 'charset' => 'utf8'],
		'city' => ['type' => 'string', 'null' => false, 'default' => null, 'length' => 100, 'collate' => 'utf8_unicode_ci', 'comment' => '', 'charset' => 'utf8'],
		'lat' => ['type' => 'float', 'null' => false, 'default' => '0.000000', 'length' => '10,6', 'collate' => null, 'comment' => 'maps.google.de latitude'],
		'lng' => ['type' => 'float', 'null' => false, 'default' => '0.000000', 'length' => '10,6', 'collate' => null, 'comment' => 'maps.google.de longitude'],
		'last_used' => ['type' => 'datetime', 'null' => true, 'default' => null, 'collate' => null, 'comment' => ''],
		'formatted_address' => ['type' => 'string', 'null' => false, 'default' => null, 'length' => 100, 'collate' => 'utf8_unicode_ci', 'comment' => '', 'charset' => 'utf8'],
		'created' => ['type' => 'datetime', 'null' => true, 'default' => null, 'collate' => null, 'comment' => ''],
		'modified' => ['type' => 'datetime', 'null' => true, 'default' => null, 'collate' => null, 'comment' => ''],
		'type_id' => ['type' => 'integer', 'null' => true, 'default' => null, 'length' => 4, 'collate' => null, 'comment' => ''],
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
			'id' => '46',
			'foreign_id' => '6',
			'model' => 'Partner',
			'country_id' => null,
			'first_name' => 'Hans',
			'last_name' => 'Wurst',
			'street' => 'Langstrasse 10',
			'postal_code' => '101010',
			'city' => 'München',
			'lat' => '48.150589',
			'lng' => '11.472230',
			'last_used' => null,
			'formatted_address' => 'Josef-Lang-Straße 10, 81245 München, Deutschland',
			'created' => '2011-04-21 16:50:05',
			'modified' => '2011-10-07 17:42:27',
			'type_id' => null
		],
		[
			'id' => '47',
			'foreign_id' => '4',
			'model' => 'Restaurant',
			'country_id' => '1',
			'first_name' => '',
			'last_name' => '',
			'street' => 'Leckermannstrasse 10',
			'postal_code' => '101010',
			'city' => 'München',
			'lat' => '48.133942',
			'lng' => '11.490000',
			'last_used' => '2031-01-01 00:00:00',
			'formatted_address' => 'Eckermannstraße 10, 80689 München, Deutschland',
			'created' => '2011-04-21 16:51:01',
			'modified' => '2011-10-07 17:44:02',
			'type_id' => null
		],
		[
			'id' => '48',
			'foreign_id' => '7',
			'model' => 'Partner',
			'country_id' => null,
			'first_name' => 'Tim',
			'last_name' => 'Schoror',
			'street' => 'Krebenweg 11',
			'postal_code' => '12523',
			'city' => 'Schwäbisch Boll',
			'lat' => '19.081490',
			'lng' => '19.690800',
			'last_used' => null,
			'formatted_address' => 'Krebenweg 11, 12523 Schwäbisch Boll, Deutschland',
			'created' => '2011-11-17 13:47:36',
			'modified' => '2011-11-17 13:47:36',
			'type_id' => null
		],
		[
			'id' => '49',
			'foreign_id' => '5',
			'model' => 'Restaurant',
			'country_id' => '1',
			'first_name' => '',
			'last_name' => '',
			'street' => 'hjsf',
			'postal_code' => 'hsjsdf',
			'city' => 'sdhfhj',
			'lat' => '0.000000',
			'lng' => '0.000000',
			'last_used' => null,
			'formatted_address' => '',
			'created' => '2011-11-17 14:34:14',
			'modified' => '2011-11-17 14:49:21',
			'type_id' => null
		],
	];

}
