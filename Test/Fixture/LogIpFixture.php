<?php
/* LogIp Fixture generated on: 2011-11-20 21:59:09 : 1321822749 */

/**
 * LogIpFixture
 *
 */
class LogIpFixture extends CakeTestFixture {

	/**
	 * Fields
	 *
	 * @var array
	 */
	public $fields = array(
		'id' => array('type' => 'integer', 'null' => false, 'default' => null, 'length' => 10, 'key' => 'primary', 'collate' => null, 'comment' => ''),
		'ip' => array('type' => 'string', 'null' => false, 'default' => null, 'length' => 39, 'key' => 'index', 'collate' => 'utf8_unicode_ci', 'comment' => '15chars=IPv4, 39chars=IPv6', 'charset' => 'utf8'),
		'referer' => array('type' => 'string', 'null' => false, 'default' => null, 'collate' => 'utf8_unicode_ci', 'comment' => '', 'charset' => 'utf8'),
		'host' => array('type' => 'string', 'null' => false, 'default' => null, 'length' => 128, 'collate' => 'utf8_unicode_ci', 'comment' => '', 'charset' => 'utf8'),
		'agent' => array('type' => 'string', 'null' => false, 'default' => null, 'collate' => 'utf8_unicode_ci', 'comment' => 'browser', 'charset' => 'utf8'),
		'user_id' => array('type' => 'string', 'null' => false, 'default' => null, 'length' => 36, 'key' => 'index', 'collate' => 'utf8_unicode_ci', 'comment' => '', 'charset' => 'utf8'),
		'lat' => array('type' => 'float', 'null' => false, 'default' => '0.000000', 'length' => '10,6', 'collate' => null, 'comment' => ''),
		'lng' => array('type' => 'float', 'null' => false, 'default' => '0.000000', 'length' => '10,6', 'collate' => null, 'comment' => ''),
		'city' => array('type' => 'string', 'null' => false, 'default' => null, 'length' => 60, 'collate' => 'utf8_unicode_ci', 'comment' => '', 'charset' => 'utf8'),
		'created' => array('type' => 'datetime', 'null' => false, 'default' => null, 'collate' => null, 'comment' => 'each ip is only valid for a few hours'),
		'indexes' => array('PRIMARY' => array('column' => 'id', 'unique' => 1), 'user_id' => array('column' => 'user_id', 'unique' => 0), 'ip' => array('column' => 'ip', 'unique' => 0)),
		'tableParameters' => array()
	);

	/**
	 * Records
	 *
	 * @var array
	 */
	public $records = array(
		array(
			'id' => '163',
			'ip' => '127.0.0.1',
			'referer' => '',
			'host' => 'marknb',
			'agent' => 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/534.30 (KHTML, like Gecko) Chrome/12.0.742.112 Safari/534.30',
			'user_id' => '0',
			'lat' => '0.000000',
			'lng' => '0.000000',
			'city' => '',
			'created' => '2011-07-17 14:14:15'
		),
		array(
			'id' => '164',
			'ip' => '127.0.0.1',
			'referer' => '',
			'host' => 'marknb',
			'agent' => 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/534.30 (KHTML, like Gecko) Chrome/12.0.742.122 Safari/534.30',
			'user_id' => '0',
			'lat' => '0.000000',
			'lng' => '0.000000',
			'city' => '',
			'created' => '2011-07-23 11:59:48'
		),
		array(
			'id' => '165',
			'ip' => '127.0.0.1',
			'referer' => '',
			'host' => 'marknb',
			'agent' => 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/534.30 (KHTML, like Gecko) Chrome/12.0.742.122 Safari/534.30',
			'user_id' => '0',
			'lat' => '0.000000',
			'lng' => '0.000000',
			'city' => '',
			'created' => '2011-07-26 02:17:14'
		),
		array(
			'id' => '166',
			'ip' => '127.0.0.1',
			'referer' => 'http://ordofood/login',
			'host' => 'marknb',
			'agent' => 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/534.30 (KHTML, like Gecko) Chrome/12.0.742.122 Safari/534.30',
			'user_id' => '14',
			'lat' => '0.000000',
			'lng' => '0.000000',
			'city' => '',
			'created' => '2011-08-07 15:26:13'
		),
		array(
			'id' => '167',
			'ip' => '127.0.0.1',
			'referer' => '',
			'host' => 'marknb',
			'agent' => 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/535.1 (KHTML, like Gecko) Chrome/13.0.782.112 Safari/535.1',
			'user_id' => '',
			'lat' => '0.000000',
			'lng' => '0.000000',
			'city' => '',
			'created' => '2011-08-10 20:21:15'
		),
		array(
			'id' => '168',
			'ip' => '127.0.0.1',
			'referer' => '',
			'host' => 'marknb',
			'agent' => 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:5.0) Gecko/20100101 Firefox/5.0',
			'user_id' => '',
			'lat' => '0.000000',
			'lng' => '0.000000',
			'city' => '',
			'created' => '2011-08-12 19:14:53'
		),
		array(
			'id' => '169',
			'ip' => '127.0.0.1',
			'referer' => '',
			'host' => 'marknb',
			'agent' => 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/535.1 (KHTML, like Gecko) Chrome/13.0.782.112 Safari/535.1',
			'user_id' => '',
			'lat' => '0.000000',
			'lng' => '0.000000',
			'city' => '',
			'created' => '2011-08-18 23:18:28'
		),
		array(
			'id' => '170',
			'ip' => '127.0.0.1',
			'referer' => '',
			'host' => 'marknb',
			'agent' => 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/535.1 (KHTML, like Gecko) Chrome/13.0.782.112 Safari/535.1',
			'user_id' => '',
			'lat' => '0.000000',
			'lng' => '0.000000',
			'city' => '',
			'created' => '2011-08-23 17:59:35'
		),
		array(
			'id' => '171',
			'ip' => '127.0.0.1',
			'referer' => '',
			'host' => 'marknb',
			'agent' => 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/535.1 (KHTML, like Gecko) Chrome/13.0.782.220 Safari/535.1',
			'user_id' => '',
			'lat' => '0.000000',
			'lng' => '0.000000',
			'city' => '',
			'created' => '2011-09-16 10:19:34'
		),
		array(
			'id' => '172',
			'ip' => '127.0.0.1',
			'referer' => '',
			'host' => 'marknb',
			'agent' => 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/535.1 (KHTML, like Gecko) Chrome/14.0.835.186 Safari/535.1',
			'user_id' => '',
			'lat' => '0.000000',
			'lng' => '0.000000',
			'city' => '',
			'created' => '2011-09-23 10:18:43'
		),
	);
}
