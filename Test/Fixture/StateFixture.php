<?php
/* State Fixture generated on: 2011-11-20 21:59:38 : 1321822778 */

/**
 * StateFixture
 *
 */
class StateFixture extends CakeTestFixture {

	/**
	 * Fields
	 *
	 * @var array
	 */
	public $fields = array(
		'id' => array('type' => 'integer', 'null' => false, 'default' => null, 'length' => 10, 'key' => 'primary', 'collate' => null, 'comment' => ''),
		'country_id' => array('type' => 'integer', 'null' => false, 'default' => '0', 'length' => 10, 'collate' => null, 'comment' => ''),
		'name' => array('type' => 'string', 'null' => false, 'default' => null, 'collate' => 'utf8_unicode_ci', 'comment' => '', 'charset' => 'utf8'),
		'abbr' => array('type' => 'string', 'null' => false, 'default' => null, 'length' => 3, 'collate' => 'utf8_unicode_ci', 'comment' => '', 'charset' => 'utf8'),
		'lat' => array('type' => 'float', 'null' => false, 'default' => '0.000000', 'length' => '10,6', 'collate' => null, 'comment' => ''),
		'lng' => array('type' => 'float', 'null' => false, 'default' => '0.000000', 'length' => '10,6', 'collate' => null, 'comment' => ''),
		'slug' => array('type' => 'string', 'null' => false, 'default' => null, 'key' => 'index', 'collate' => 'utf8_unicode_ci', 'comment' => '', 'charset' => 'utf8'),
		'modified' => array('type' => 'datetime', 'null' => false, 'default' => null, 'collate' => null, 'comment' => ''),
		'indexes' => array('PRIMARY' => array('column' => 'id', 'unique' => 1), 'slug' => array('column' => 'slug', 'unique' => 0)),
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
			'country_id' => '1',
			'name' => 'Schleswig-Holstein',
			'abbr' => '',
			'lat' => '0.000000',
			'lng' => '0.000000',
			'slug' => 'schleswig-holstein',
			'modified' => '0000-00-00 00:00:00'
		),
		array(
			'id' => '2',
			'country_id' => '1',
			'name' => 'Hamburg',
			'abbr' => '',
			'lat' => '0.000000',
			'lng' => '0.000000',
			'slug' => 'hamburg',
			'modified' => '0000-00-00 00:00:00'
		),
		array(
			'id' => '3',
			'country_id' => '1',
			'name' => 'Niedersachsen',
			'abbr' => '',
			'lat' => '0.000000',
			'lng' => '0.000000',
			'slug' => 'niedersachsen',
			'modified' => '0000-00-00 00:00:00'
		),
		array(
			'id' => '4',
			'country_id' => '1',
			'name' => 'Bremen',
			'abbr' => '',
			'lat' => '0.000000',
			'lng' => '0.000000',
			'slug' => 'bremen',
			'modified' => '0000-00-00 00:00:00'
		),
		array(
			'id' => '5',
			'country_id' => '1',
			'name' => 'Nordrhein-Westfalen',
			'abbr' => '',
			'lat' => '0.000000',
			'lng' => '0.000000',
			'slug' => 'nordrhein-westfalen',
			'modified' => '0000-00-00 00:00:00'
		),
		array(
			'id' => '6',
			'country_id' => '1',
			'name' => 'Hessen',
			'abbr' => '',
			'lat' => '0.000000',
			'lng' => '0.000000',
			'slug' => 'hessen',
			'modified' => '0000-00-00 00:00:00'
		),
		array(
			'id' => '7',
			'country_id' => '1',
			'name' => 'Rheinland-Pfalz',
			'abbr' => '',
			'lat' => '0.000000',
			'lng' => '0.000000',
			'slug' => 'rheinland-pfalz',
			'modified' => '0000-00-00 00:00:00'
		),
		array(
			'id' => '8',
			'country_id' => '1',
			'name' => 'Baden-Württemberg',
			'abbr' => '',
			'lat' => '0.000000',
			'lng' => '0.000000',
			'slug' => 'baden-wuerttemberg',
			'modified' => '0000-00-00 00:00:00'
		),
		array(
			'id' => '9',
			'country_id' => '1',
			'name' => 'Bayern',
			'abbr' => '',
			'lat' => '0.000000',
			'lng' => '0.000000',
			'slug' => 'bayern',
			'modified' => '0000-00-00 00:00:00'
		),
		array(
			'id' => '10',
			'country_id' => '1',
			'name' => 'Saarland',
			'abbr' => '',
			'lat' => '0.000000',
			'lng' => '0.000000',
			'slug' => 'saarland',
			'modified' => '0000-00-00 00:00:00'
		),
	);
}
