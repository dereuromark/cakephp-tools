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
	public $fields = [
		'id' => ['type' => 'integer', 'null' => false, 'default' => null, 'length' => 10, 'key' => 'primary', 'collate' => null, 'comment' => ''],
		'ip' => ['type' => 'string', 'null' => false, 'default' => null, 'length' => 39, 'key' => 'index', 'collate' => 'utf8_unicode_ci', 'comment' => '15chars=IPv4, 39chars=IPv6', 'charset' => 'utf8'],
		'referer' => ['type' => 'string', 'null' => false, 'default' => null, 'collate' => 'utf8_unicode_ci', 'comment' => '', 'charset' => 'utf8'],
		'host' => ['type' => 'string', 'null' => false, 'default' => null, 'length' => 128, 'collate' => 'utf8_unicode_ci', 'comment' => '', 'charset' => 'utf8'],
		'agent' => ['type' => 'string', 'null' => false, 'default' => null, 'collate' => 'utf8_unicode_ci', 'comment' => 'browser', 'charset' => 'utf8'],
		'user_id' => ['type' => 'string', 'null' => true, 'default' => null, 'length' => 36, 'key' => 'index', 'collate' => 'utf8_unicode_ci', 'comment' => '', 'charset' => 'utf8'],
		'lat' => ['type' => 'float', 'null' => false, 'default' => '0.000000', 'length' => '10,6', 'collate' => null, 'comment' => ''],
		'lng' => ['type' => 'float', 'null' => false, 'default' => '0.000000', 'length' => '10,6', 'collate' => null, 'comment' => ''],
		'city' => ['type' => 'string', 'null' => false, 'default' => null, 'length' => 60, 'collate' => 'utf8_unicode_ci', 'comment' => '', 'charset' => 'utf8'],
		'created' => ['type' => 'datetime', 'null' => true, 'default' => null, 'collate' => null, 'comment' => 'each ip is only valid for a few hours'],
		'indexes' => ['PRIMARY' => ['column' => 'id', 'unique' => 1], 'user_id' => ['column' => 'user_id', 'unique' => 0], 'ip' => ['column' => 'ip', 'unique' => 0]],
		'tableParameters' => []
	];

	/**
	 * Records
	 *
	 * @var array
	 */
	public $records = [
		[
			'id' => '163',
			'ip' => '127.0.0.1',
			'referer' => '',
			'host' => 'marknb',
			'agent' => 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/534.30 (KHTML, like Gecko) Chrome/12.0.742.112 Safari/534.30',
			'user_id' => null,
			'lat' => '0.000000',
			'lng' => '0.000000',
			'city' => '',
			'created' => '2011-07-17 14:14:15'
		],
		[
			'id' => '164',
			'ip' => '127.0.0.1',
			'referer' => '',
			'host' => 'marknb',
			'agent' => 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/534.30 (KHTML, like Gecko) Chrome/12.0.742.122 Safari/534.30',
			'user_id' => null,
			'lat' => '0.000000',
			'lng' => '0.000000',
			'city' => '',
			'created' => '2011-07-23 11:59:48'
		],
		[
			'id' => '165',
			'ip' => '127.0.0.1',
			'referer' => '',
			'host' => 'marknb',
			'agent' => 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/534.30 (KHTML, like Gecko) Chrome/12.0.742.122 Safari/534.30',
			'user_id' => null,
			'lat' => '0.000000',
			'lng' => '0.000000',
			'city' => '',
			'created' => '2011-07-26 02:17:14'
		],
		[
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
		],
		[
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
		],
		[
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
		],
		[
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
		],
		[
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
		],
		[
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
		],
		[
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
		],
	];
}
