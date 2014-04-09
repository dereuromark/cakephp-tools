<?php
/* CountryProvince Fixture generated on: 2011-11-20 21:58:52 : 1321822732 */

/**
 * CountryProvinceFixture
 *
 */
class CountryProvinceFixture extends CakeTestFixture {

	/**
	 * Fields
	 *
	 * @var array
	 */
	public $fields = array(
		'id' => array('type' => 'integer', 'null' => false, 'default' => null, 'length' => 10, 'key' => 'primary', 'collate' => null, 'comment' => ''),
		'country_id' => array('type' => 'integer', 'null' => false, 'default' => '0', 'length' => 10, 'collate' => null, 'comment' => ''),
		'abbr' => array('type' => 'string', 'null' => false, 'default' => null, 'length' => 3, 'collate' => 'utf8_unicode_ci', 'comment' => '', 'charset' => 'utf8'),
		'name' => array('type' => 'string', 'null' => false, 'default' => null, 'length' => 40, 'collate' => 'utf8_unicode_ci', 'comment' => '', 'charset' => 'utf8'),
		'lat' => array('type' => 'float', 'null' => false, 'default' => '0.000000', 'length' => '10,6', 'collate' => null, 'comment' => ''),
		'lng' => array('type' => 'float', 'null' => false, 'default' => '0.000000', 'length' => '10,6', 'collate' => null, 'comment' => ''),
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
			'country_id' => '1',
			'abbr' => 'BAY',
			'name' => 'Bayern',
			'lat' => '48.790447',
			'lng' => '11.497889',
			'modified' => '2009-11-27 04:10:31'
		),
		array(
			'id' => '2',
			'country_id' => '1',
			'abbr' => 'BBG',
			'name' => 'Brandenburg',
			'lat' => '52.408417',
			'lng' => '12.562492',
			'modified' => '2009-11-27 04:10:32'
		),
		array(
			'id' => '3',
			'country_id' => '1',
			'abbr' => 'BER',
			'name' => 'Berlin',
			'lat' => '52.523403',
			'lng' => '13.411400',
			'modified' => '2009-11-27 04:10:31'
		),
		array(
			'id' => '4',
			'country_id' => '1',
			'abbr' => 'BRE',
			'name' => 'Bremen',
			'lat' => '53.074982',
			'lng' => '8.807081',
			'modified' => '2009-11-27 04:10:32'
		),
		array(
			'id' => '5',
			'country_id' => '1',
			'abbr' => 'BW',
			'name' => 'Baden-Württemberg',
			'lat' => '48.661606',
			'lng' => '9.350134',
			'modified' => '2009-11-27 04:10:31'
		),
		array(
			'id' => '6',
			'country_id' => '1',
			'abbr' => 'HES',
			'name' => 'Hessen',
			'lat' => '50.652050',
			'lng' => '9.162438',
			'modified' => '2009-11-27 04:10:38'
		),
		array(
			'id' => '7',
			'country_id' => '1',
			'abbr' => 'HH',
			'name' => 'Hamburg',
			'lat' => '53.553406',
			'lng' => '9.992196',
			'modified' => '2009-11-27 04:10:37'
		),
		array(
			'id' => '8',
			'country_id' => '1',
			'abbr' => 'MVP',
			'name' => 'Mecklenburg-Vorp.',
			'lat' => '0.000000',
			'lng' => '0.000000',
			'modified' => '0000-00-00 00:00:00'
		),
		array(
			'id' => '9',
			'country_id' => '1',
			'abbr' => 'NDS',
			'name' => 'Niedersachsen',
			'lat' => '52.636703',
			'lng' => '9.845076',
			'modified' => '2009-11-27 04:10:49'
		),
		array(
			'id' => '10',
			'country_id' => '1',
			'abbr' => 'NRW',
			'name' => 'Nordrhein-Westfalen',
			'lat' => '51.433235',
			'lng' => '7.661594',
			'modified' => '2009-11-27 04:10:49'
		),
	);
}
