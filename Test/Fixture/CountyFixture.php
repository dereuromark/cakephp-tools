<?php
/* County Fixture generated on: 2011-11-20 21:58:50 : 1321822730 */

/**
 * CountyFixture
 *
 */
class CountyFixture extends CakeTestFixture {

	/**
	 * Fields
	 *
	 * @var array
	 */
	public $fields = array(
		'id' => array('type' => 'integer', 'null' => false, 'default' => null, 'length' => 10, 'key' => 'primary', 'collate' => null, 'comment' => ''),
		'official_id' => array('type' => 'string', 'null' => false, 'default' => null, 'length' => 9, 'key' => 'unique', 'collate' => 'utf8_unicode_ci', 'comment' => '', 'charset' => 'utf8'),
		'country_id' => array('type' => 'integer', 'null' => false, 'default' => '0', 'length' => 10, 'key' => 'index', 'collate' => null, 'comment' => ''),
		'state_id' => array('type' => 'integer', 'null' => false, 'default' => '0', 'length' => 10, 'key' => 'index', 'collate' => null, 'comment' => ''),
		'name' => array('type' => 'string', 'null' => false, 'default' => null, 'collate' => 'utf8_unicode_ci', 'comment' => '', 'charset' => 'utf8'),
		'lat' => array('type' => 'float', 'null' => false, 'default' => '0.000000', 'length' => '10,6', 'collate' => null, 'comment' => ''),
		'lng' => array('type' => 'float', 'null' => false, 'default' => '0.000000', 'length' => '10,6', 'collate' => null, 'comment' => ''),
		'slug' => array('type' => 'string', 'null' => false, 'default' => null, 'key' => 'unique', 'collate' => 'utf8_unicode_ci', 'comment' => '', 'charset' => 'utf8'),
		'single_city' => array('type' => 'boolean', 'null' => false, 'default' => '0', 'collate' => null, 'comment' => ''),
		'rank' => array('type' => 'integer', 'null' => false, 'default' => null, 'length' => 5, 'key' => 'index', 'collate' => null, 'comment' => ''),
		'modified' => array('type' => 'datetime', 'null' => false, 'default' => null, 'collate' => null, 'comment' => ''),
		'indexes' => array('PRIMARY' => array('column' => 'id', 'unique' => 1), 'official_id' => array('column' => 'official_id', 'unique' => 1), 'slug' => array('column' => 'slug', 'unique' => 1), 'country' => array('column' => 'country_id', 'unique' => 0), 'state_id' => array('column' => 'state_id', 'unique' => 0), 'rank' => array('column' => 'rank', 'unique' => 0)),
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
			'official_id' => '01-0-01',
			'country_id' => '1',
			'state_id' => '1',
			'name' => 'Flensburg',
			'lat' => '0.000000',
			'lng' => '0.000000',
			'slug' => 'flensburg',
			'single_city' => 1,
			'rank' => '1',
			'modified' => '0000-00-00 00:00:00'
		),
		array(
			'id' => '2',
			'official_id' => '01-0-02',
			'country_id' => '1',
			'state_id' => '1',
			'name' => 'Kiel',
			'lat' => '0.000000',
			'lng' => '0.000000',
			'slug' => 'kiel',
			'single_city' => 1,
			'rank' => '3',
			'modified' => '0000-00-00 00:00:00'
		),
		array(
			'id' => '3',
			'official_id' => '01-0-03',
			'country_id' => '1',
			'state_id' => '1',
			'name' => 'Lübeck',
			'lat' => '0.000000',
			'lng' => '0.000000',
			'slug' => 'luebeck',
			'single_city' => 1,
			'rank' => '4',
			'modified' => '0000-00-00 00:00:00'
		),
		array(
			'id' => '4',
			'official_id' => '01-0-04',
			'country_id' => '1',
			'state_id' => '1',
			'name' => 'Neumünster',
			'lat' => '0.000000',
			'lng' => '0.000000',
			'slug' => 'neumuenster',
			'single_city' => 1,
			'rank' => '5',
			'modified' => '0000-00-00 00:00:00'
		),
		array(
			'id' => '5',
			'official_id' => '02-0-00',
			'country_id' => '1',
			'state_id' => '2',
			'name' => 'Hamburg',
			'lat' => '0.000000',
			'lng' => '0.000000',
			'slug' => 'hamburg',
			'single_city' => 1,
			'rank' => '0',
			'modified' => '0000-00-00 00:00:00'
		),
		array(
			'id' => '6',
			'official_id' => '03-1-01',
			'country_id' => '1',
			'state_id' => '3',
			'name' => 'Braunschweig',
			'lat' => '0.000000',
			'lng' => '0.000000',
			'slug' => 'braunschweig',
			'single_city' => 1,
			'rank' => '2',
			'modified' => '0000-00-00 00:00:00'
		),
		array(
			'id' => '7',
			'official_id' => '03-1-02',
			'country_id' => '1',
			'state_id' => '3',
			'name' => 'Salzgitter',
			'lat' => '0.000000',
			'lng' => '0.000000',
			'slug' => 'salzgitter',
			'single_city' => 1,
			'rank' => '34',
			'modified' => '0000-00-00 00:00:00'
		),
		array(
			'id' => '8',
			'official_id' => '03-1-03',
			'country_id' => '1',
			'state_id' => '3',
			'name' => 'Wolfsburg',
			'lat' => '0.000000',
			'lng' => '0.000000',
			'slug' => 'wolfsburg',
			'single_city' => 1,
			'rank' => '45',
			'modified' => '0000-00-00 00:00:00'
		),
		array(
			'id' => '9',
			'official_id' => '03-4-01',
			'country_id' => '1',
			'state_id' => '3',
			'name' => 'Delmenhorst',
			'lat' => '0.000000',
			'lng' => '0.000000',
			'slug' => 'delmenhorst',
			'single_city' => 1,
			'rank' => '6',
			'modified' => '0000-00-00 00:00:00'
		),
		array(
			'id' => '10',
			'official_id' => '03-4-02',
			'country_id' => '1',
			'state_id' => '3',
			'name' => 'Emden',
			'lat' => '0.000000',
			'lng' => '0.000000',
			'slug' => 'emden',
			'single_city' => 1,
			'rank' => '8',
			'modified' => '0000-00-00 00:00:00'
		),
	);
}
