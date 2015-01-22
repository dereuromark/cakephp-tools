<?php
/* Location Fixture generated on: 2011-11-20 21:59:08 : 1321822748 */

/**
 * LocationFixture
 *
 */
class LocationFixture extends CakeTestFixture {

	/**
	 * Fields
	 *
	 * @var array
	 */
	public $fields = [
		'id' => ['type' => 'integer', 'null' => false, 'default' => null, 'length' => 10, 'key' => 'primary', 'collate' => null, 'comment' => ''],
		'name' => ['type' => 'string', 'null' => false, 'default' => null, 'length' => 50, 'collate' => 'utf8_general_ci', 'comment' => '', 'charset' => 'utf8'],
		'country_id' => ['type' => 'integer', 'null' => true, 'default' => null, 'length' => 10, 'collate' => null, 'comment' => ''],
		'lat' => ['type' => 'float', 'null' => false, 'default' => null, 'collate' => null, 'comment' => ''],
		'lng' => ['type' => 'float', 'null' => false, 'default' => null, 'collate' => null, 'comment' => ''],
		'formatted_address' => ['type' => 'string', 'null' => false, 'default' => null, 'collate' => 'utf8_unicode_ci', 'comment' => '', 'charset' => 'utf8'],
		'locality' => ['type' => 'string', 'null' => false, 'default' => null, 'length' => 50, 'collate' => 'utf8_unicode_ci', 'comment' => '', 'charset' => 'utf8'],
		'sublocality' => ['type' => 'string', 'null' => false, 'default' => null, 'length' => 50, 'collate' => 'utf8_unicode_ci', 'comment' => '', 'charset' => 'utf8'],
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
			'name' => 'münchen',
			'country_id' => '1',
			'lat' => '48.1391',
			'lng' => '11.5802',
			'formatted_address' => 'München, Deutschland',
			'locality' => '',
			'sublocality' => '0',
			'created' => '2011-10-22 14:33:48',
			'modified' => '2011-10-22 14:33:48'
		],
		[
			'id' => '2',
			'name' => 'Allach',
			'country_id' => '1',
			'lat' => '48.1971',
			'lng' => '11.4582',
			'formatted_address' => 'Allach, 80999 München, Deutschland',
			'locality' => '',
			'sublocality' => '0',
			'created' => '2011-10-22 14:34:35',
			'modified' => '2011-10-22 14:34:35'
		],
		[
			'id' => '3',
			'name' => 'Sendling',
			'country_id' => '1',
			'lat' => '48.1199',
			'lng' => '11.5407',
			'formatted_address' => 'Sendling, München, Deutschland',
			'locality' => 'München',
			'sublocality' => 'Sendling',
			'created' => '2011-10-22 14:45:10',
			'modified' => '2011-10-22 14:45:10'
		],
		[
			'id' => '4',
			'name' => 'Obermenzing',
			'country_id' => '1',
			'lat' => '48.1648',
			'lng' => '11.4671',
			'formatted_address' => 'Obermenzing, 81247 München, Deutschland',
			'locality' => 'München',
			'sublocality' => 'Obermenzing',
			'created' => '2011-10-22 14:46:48',
			'modified' => '2011-10-22 14:46:48'
		],
		[
			'id' => '5',
			'name' => '74523',
			'country_id' => '1',
			'lat' => '49.1258',
			'lng' => '9.75441',
			'formatted_address' => '74523 Schwäbisch Hall, Deutschland',
			'locality' => 'Schwäbisch Hall',
			'sublocality' => '',
			'created' => '2011-10-22 14:47:06',
			'modified' => '2011-10-22 14:47:06'
		],
		[
			'id' => '11',
			'name' => 'thiemestr. 7',
			'country_id' => '1',
			'lat' => '48.157',
			'lng' => '11.5886',
			'formatted_address' => 'Thiemestraße 7, 80802 München, Deutschland',
			'locality' => 'München',
			'sublocality' => 'München',
			'created' => '2011-10-22 15:09:14',
			'modified' => '2011-10-22 15:09:14'
		],
		[
			'id' => '12',
			'name' => 'thiemestr. 8',
			'country_id' => '1',
			'lat' => '51.2923',
			'lng' => '12.4539',
			'formatted_address' => 'Clemens-Thieme-Straße 8, 04288 Leipzig, Deutschland',
			'locality' => 'Leipzig',
			'sublocality' => 'Liebertwolkwitz',
			'created' => '2011-10-22 15:09:24',
			'modified' => '2011-10-22 15:09:24'
		],
		[
			'id' => '13',
			'name' => 'neufahrn',
			'country_id' => '1',
			'lat' => '48.1141',
			'lng' => '11.1633',
			'formatted_address' => 'Neufahrn bei Freising, Deutschland',
			'locality' => 'Neufahrn bei Freising',
			'sublocality' => '',
			'created' => '2011-10-22 15:15:12',
			'modified' => '2011-10-22 15:15:12'
		],
		[
			'id' => '15',
			'name' => '85375',
			'country_id' => '1',
			'lat' => '48.1118',
			'lng' => '11.1671',
			'formatted_address' => '85375 Neufahrn bei Freising, Deutschland',
			'locality' => 'Neufahrn bei Freising',
			'sublocality' => '',
			'created' => '2011-10-22 15:17:16',
			'modified' => '2011-10-22 15:17:16'
		],
		[
			'id' => '18',
			'name' => 'hamburg',
			'country_id' => '1',
			'lat' => '53.5538',
			'lng' => '9.99159',
			'formatted_address' => 'Hamburg, Deutschland',
			'locality' => 'Hamburg',
			'sublocality' => '',
			'created' => '2011-10-22 16:10:04',
			'modified' => '2011-10-22 16:10:04'
		],
	];
}
