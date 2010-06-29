<?php

/**
 * geocode via google (UPDATE: api3)
 * @see DEPRECATED api2: http://code.google.com/intl/de-DE/apis/maps/articles/phpsqlgeocode.html
 * @sse http://code.google.com/intl/de/apis/maps/documentation/geocoding/#Types
 * 2010-06-25 ms
 */
class GeocodeLib {

	//const BASE_URL = 'http://{host}/maps/geo?output={output}&oe=utf8&key={key}&q='; // deprecated
	const BASE_URL = 'http://{host}/maps/api/geocode/{output}?';
	const DEFAULT_HOST = 'us';

	# First tries with curl, then cake, then php
	var $use = array('curl' => true, 'cake'=> true, 'php' => true);
	var $log = false; # false logs only real errors, true all activities

	var $units = array('K' => 1.609344, 'N' => 0.868976242, 'F' => 5280, 'I' => 63360, 'M' => 1);

	/**
	 * validation and retrieval
	 * 2010-06-25 ms
	 */
	private $options = array(
		'pause' => 10000,
		'min_accuracy' => 1,
		'allow_inconclusive'=> true,
		# static url params
		'output' => 'xml',
		'host' => self::DEFAULT_HOST, # results in maps.google.com - use if you wish to obtain the closest address
	);

	/**
	 * url params
	 * 2010-06-25 ms
	 */
	private $params = array(
		'address' => '', # either address or latlng required!
		'latlng' => '',
		'region' => '', # country tlds
		'language' => 'de',
		'bounds' => '',
		'sensor' => 'false', # device with gps module sensor
		//'key' => '' # not neccessary anymore
 	);

	private $error = array();
	private $result = null;

	/**
	 * The Maps geocoder is programmed to bias its results depending on from which domain it receives requests. For example, entering "syracuse" in the search box on maps.google.com will geocode the city of "Syracuse, NY", while entering the same query on maps.google.it (Italy's domain) will find the city of "Siracusa" in Sicily. You would get the same results by sending that query through HTTP geocoding to maps.google.it instead of maps.google.com, which you can do by modifying the MAPS_HOST constant in the sample code below. Note: You cannot send a request to a non-existent maps.google.* server, so ensure that a country domain exists before redirecting your geocoding queries to it.
	 */
	private $hosts = array(
		'us' => 'maps.google.com', # only one for "allow_inconclusive" = true
		'gb' => 'maps.google.co.uk',
		'de' => 'maps.google.de',
		'ch' => 'maps.google.ch',
		'at' => 'maps.google.at',
		'it' => 'maps.google.it',
 		//ADD MORE - The two-letter codes are iso2 country codes and are mapped to top level domains (ccTLDs)
	);

	private $statusCodes = array(
		self::CODE_SUCCESS => 'Success',
		self::CODE_BAD_REQUEST => 'Sensor param missing',
		self::CODE_MISSING_QUERY => 'Adress/LatLng missing',
		self::CODE_UNKNOWN_ADDRESS => 'Success, but to address found',
		self::CODE_TOO_MANY_QUERIES => 'Limit exceeded',
	);

	private $accuracyTypes = array(
		0 => 'country',
		1 => 'administrative_area_level_1', # provinces/states
		2 => 'administrative_area_level_2 ',
		3 => 'administrative_area_level_3',
		4 => 'postal_code',
		5 => 'locality',
		5 => 'sublocality',
		6 => 'route',
		7 => 'intersection',
		8 => 'street_address'
		//neighborhood premise subpremise natural_feature airport park point_of_interest colloquial_area political ?
	);

	function __construct($options = array()) {
		/*
		if ($googleKey = Configure::read('Google.key')) {
			$this->params['key'] = $googleKey;
		}
		*/
		$this->defaultParams = $this->params;
		$this->defaultOptions = $this->options;
		$this->setOptions($options);
	}


	function setParams($params) {
		foreach ($params as $key => $value) {
			if ($key == 'sensor' && $value != 'false' && $value != 'true') {
				$value = !empty($value) ? 'true' : 'false';
			}
			$this->params[$key] = urlencode((string)$value);
		}
	}

	function setOptions($options) {
		foreach ($options as $key => $value) {
			if ($key == 'output' && $value != 'xml' && $value != 'json') {
				continue;
			}
			if ($key == 'host' && !array_key_exists($value, $this->hosts)) {
				continue;
			}
			$this->options[$key] = $value;
		}
	}

	function setError($error) {
		if (empty($error)) {
			return;
		}
		$this->error[] = $error;
	}

	function error($asString = true, $separator = ', ') {
		return implode(', ', $this->error);
	}


	function reset($full = true) {
		$this->error = array();
		$this->result = null;
		if ($full) {
			$this->params = $this->defaultParams;
			$this->options = $this->defaultOptions;
		}
	}


	/**
	 * build and return url
	 * 2010-06-29 ms
	 */
	function url() {
		$params = array(
			'host' => $this->hosts[$this->options['host']],
			'output' => $this->options['output']
		);
		$url = String::insert(self::BASE_URL, $params, array('before'=>'{', 'after'=>'}', 'clean'=>true));
		$params = array();
		foreach ($this->params as $key => $value) {
			if (!empty($value)) {
				$params[] = $key.'='.$value;
			}
		}
		return $url.implode('&', $params);
	}


	function isInconclusive() {
		if ($this->result === null) {
			return null;
		}
		if (!isset($this->result[0])) {
			return false;
		}
		return count($this->result) > 0;
	}

	/**
	 * @return array $result
	 * 2010-06-25 ms
	 */
	function getResult() {
		if ($this->result !== null) {

			if (isset($this->result[0])) {
				$res = array();
				foreach ($this->result as $tmp) {
					$res[] = $this->options['output'] == 'json' ? $this->_transformJson($tmp) : $this->_transformXml($tmp);
				}
				return $res;
			}
			if ($this->options['output'] == 'json') {
				return $this->_transformJson($this->result);
			} else {
				return $this->_transformXml($this->result);
			}
		}
		return false;
	}

	/**
	 * results usually from most accurate to least accurate result (street_address
, ..., country)
	 * @param float $lat
	 * @param float $lng
	 * @param array $options
	 * - allow_inconclusive
	 * - min_accuracy
	 * @return boolean $success
	 * 2010-06-29 ms
	 */
	function reverseGeocode($lat, $lng, $settings = array()) {
		$this->reset(false);
		$latlng = $lat.','.$lng;
		$this->setParams(array_merge($settings, array('latlng'=>$latlng)));

		$count = 0;
		$request_url = $this->url();
		while (true) {
			$result = $this->_fetch($request_url);
			if ($result === false || $result === null) {
				$this->setError('Could not retrieve url');
				CakeLog::write('geocode', 'Geocoder could not retrieve url with \''.$address.'\'');
				return false;
			}

			if ($this->options['output'] == 'json') {
				//$res = json_decode($result);
			} else {

				App::import('Core', array('Xml'));
				$res = new Xml($result);
			}

			if (!is_object($res)) {
				$this->setError('XML parsing failed');
				CakeLog::write('geocode', 'Geocoder failed with XML parsing of \''.$address.'\'');
				return false;
			}

			$xmlArray = $res->children[0]->toArray();

			$status = $xmlArray['status'];

			if ($status == self::CODE_SUCCESS) {

				# validate
				if (isset($xmlArray['Result'][0]) && !$this->options['allow_inconclusive']) {
					$this->setError('Inconclusive result (total of '.count($xmlArray['Result']).')');
					$this->result = $xmlArray['Result'];
					return false;
				}

				if (isset($xmlArray['Result'][0])) {
					$accuracy = $this->_parse('type', $xmlArray['Result'][0]);
				} else {
					$accuracy = $this->_parse('type', $xmlArray['Result']);
				}

				if ($this->_isNotAccurateEnough($accuracy)) {
					$this->setError('Accuracy not good enough ('.$accuracy.' instead of at least '.$this->accuracyTypes[$this->options['min_accuracy']].')');
					$this->result = $xmlArray['Result'];
					return false;
				}


				# save Result
				if ($this->log) {
					CakeLog::write('geocode', 'Address \''.$address.'\' has been geocoded');
				}
				break;

			} elseif ($status == self::CODE_TOO_MANY_QUERIES) {
				// sent geocodes too fast, delay +0.1 seconds
				if ($this->log) {
					CakeLog::write('geocode', 'Delay neccessary for \''.$address.'\'');
				}
				$count++;
			} else {

				# something went wrong
				$this->setError('Error '.$status.(isset($this->statusCodes[$status]) ? ' ('.$this->statusCodes[$status].')' : ''));

				if ($this->log) {
					CakeLog::write('geocode', 'Geocoder could not geocode \''.$address.'\'');
				}
				return false; # for now...
			}
			if ($count > 5) {
				if ($this->log) {
					CakeLog::write('geocode', 'Geocoder aborted after too many trials with \''.$address.'\'');
				}
				$this->setError('Too many trials - abort');
				return false;
			}
			$this->pause(true);
		}
		$this->result = $xmlArray['Result'];
		return true;
	}


	/**
	 * trying to avoid "TOO_MANY_QUERIES" error
	 * 2010-06-29 ms
	 */
	function pause($raise = false) {
		usleep($this->settings['pause']);
		if ($raise) {
			$this->settings['pause'] += 10000;
		}
	}


	/**
	 * @param string $address
	 * @param array $settings
	 * - allow_inconclusive
	 * - min_accuracy
	 * @return boolean $success
	 * 2010-06-25 ms
	 */
	function geocode($address, $settings = array()) {
		$this->reset(false);
		$this->setParams(array_merge($settings, array('address'=>$address)));
		if ($this->options['allow_inconclusive']) {
			# only host working with this setting
			//$this->options['host'] = self::DEFAULT_HOST;
		}

		$count = 0;
		$request_url = $this->url();

		while (true) {
			$result = $this->_fetch($request_url);

			if ($result === false || $result === null) {
				$this->setError('Could not retrieve url');
				CakeLog::write('geocode', 'Geocoder could not retrieve url with \''.$address.'\'');
				return false;
			}

			if ($this->options['output'] == 'json') {
				//$res = json_decode($result);
			} else {

				App::import('Core', array('Xml'));
				$res = new Xml($result);
			}

			if (!is_object($res)) {
				$this->setError('XML parsing failed');
				CakeLog::write('geocode', 'Geocoder failed with XML parsing of \''.$address.'\'');
				return false;
			}

			$xmlArray = $res->children[0]->toArray();

			$status = $xmlArray['status'];

			if ($status == self::CODE_SUCCESS) {

				# validate
				if (isset($xmlArray['Result'][0]) && !$this->options['allow_inconclusive']) {
					$this->setError('Inconclusive result (total of '.count($xmlArray['Result']).')');
					$this->result = $xmlArray['Result'];
					return false;
				}

				if (isset($xmlArray['Result'][0])) {
					$accuracy = $this->_parse('type', $xmlArray['Result'][0]);
				} else {
					$accuracy = $this->_parse('type', $xmlArray['Result']);
				}

				if ($this->_isNotAccurateEnough($accuracy)) {
					$this->setError('Accuracy not good enough ('.$accuracy.' instead of at least '.$this->accuracyTypes[$this->options['min_accuracy']].')');
					$this->result = $xmlArray['Result'];
					return false;
				}


				# save Result
				if ($this->log) {
					CakeLog::write('geocode', 'Address \''.$address.'\' has been geocoded');
				}
				break;

			} elseif ($status == self::CODE_TOO_MANY_QUERIES) {
				// sent geocodes too fast, delay +0.1 seconds
				if ($this->log) {
					CakeLog::write('geocode', 'Delay neccessary for \''.$address.'\'');
				}
				$count++;
			} else {

				# something went wrong
				$this->setError('Error '.$status.(isset($this->statusCodes[$status]) ? ' ('.$this->statusCodes[$status].')' : ''));

				if ($this->log) {
					CakeLog::write('geocode', 'Geocoder could not geocode \''.$address.'\'');
				}
				return false; # for now...
			}
			if ($count > 5) {
				if ($this->log) {
					CakeLog::write('geocode', 'Geocoder aborted after too many trials with \''.$address.'\'');
				}
				$this->setError('Too many trials - abort');
				return false;
			}
			$this->pause(true);
		}
		$this->result = $xmlArray['Result'];
		return true;
	}

	function _isNotAccurateEnough($accuracy = null) {
		if ($accuracy === null) {
			if (isset($this->result[0])) {
				$accuracy = $this->result[0]['type'];
			} else {
				$accuracy = $this->result['type'];
			}
		}
		if (!in_array($accuracy, $this->accuracyTypes)) {
			return null;
		}
		foreach ($this->accuracyTypes as $key => $type) {
			if ($type == $accuracy) {
				$accuracy = $key;
				break;
			}
		}
		return $accuracy < $this->options['min_accuracy'];
	}


	function _transformJson($record) {
		$res = array();
		//TODO
		return $res;
	}

	/**
	 * try to find the correct path
	 * - type (string)
	 * - Type (array[string, ...])
	 * 2010-06-29 ms
	 */
	function _parse($key, $array) {
		if (isset($array[$key])) {
			return $array[$key];
		}
		if (isset($array[($key = ucfirst($key))])) {
			return $array[$key][0];
		}
		return null;
	}

	/**
	 * flattens result array and returns clean record
	 * keys:
	 * - formatted_address, type, country, country_code, country_province, country_province_code, locality, sublocality, postal_code, route, lat, lng, location_type, viewport, bounds
	 * 2010-06-25 ms
	 */
	function _transformXml($record) {
		$res = array();

		$components = array();
		if (!isset($record['AddressComponent'][0])) {
			$record['AddressComponent'] = array($record['AddressComponent']);
		}
		foreach ($record['AddressComponent'] as $c) {
			$types = array();
			if (isset($c['Type'])) { //!is_array($c['Type'])
				if (!is_array($c['Type'])) {
					echo returns($record);
					die();
				}

				$type = $c['Type'][0];
				array_shift($c['Type']);
				$types = $c['Type'];
			} else {
				$type = $c['type'];
			}
			if (array_key_exists($type, $components)) {
				$components[$type]['name'] .= ' '.$c['long_name'];
				$components[$type]['abbr'] .= ' '.$c['short_name'];
				$components[$type]['types'] += $types;
			}
			$components[$type] = array('name'=>$c['long_name'], 'abbr'=>$c['short_name'], 'types'=>$types);
		}

		$res['formatted_address'] = $record['formatted_address'];

		$res['type'] = $this->_parse('type', $record);

		if (array_key_exists('country', $components)) {
			$res['country'] = $components['country']['name'];
			$res['country_code'] = $components['country']['abbr'];
		} else {
			$res['country'] = $res['country_code'] = '';
		}
		if (array_key_exists('administrative_area_level_1', $components)) {
			$res['country_province'] = $components['administrative_area_level_1']['name'];
			$res['country_province_code'] = $components['administrative_area_level_1']['abbr'];
		} else {
			$res['country_province'] = $res['country_province_code'] = '';
		}

		if (array_key_exists('postal_code', $components)) {
			$res['postal_code'] = $components['postal_code']['name'];
		} else {
			$res['postal_code'] = '';
		}

		if (array_key_exists('locality', $components)) {
			$res['locality'] = $components['locality']['name'];
		} else {
			$res['locality'] = '';
		}

		if (array_key_exists('sublocality', $components)) {
			$res['sublocality'] = $components['sublocality']['name'];
		} else {
			$res['sublocality'] = '';
		}

		if (array_key_exists('route', $components)) {
			$res['route'] = $components['route']['name'];
			if (array_key_exists('street_number', $components)) {
				$res['route'] .= ' '.$components['street_number']['name'];
			}
		} else {
			$res['route'] = '';
		}

		//TODO: add more


		$res['lng'] = $record['Geometry']['Location']['lat'];
		$res['lat'] = $record['Geometry']['Location']['lng'];
		$res['location_type'] = $record['Geometry']['location_type'];

		if (!empty($record['Geometry']['Viewport'])) {
		$res['viewport'] = array('sw'=>$record['Geometry']['Viewport']['Southwest'], 'ne'=>$record['Geometry']['Viewport']['Northeast']);
		}
		if (!empty($record['Geometry']['Bounds'])) {
			$res['bounds'] = array('sw'=>$record['Geometry']['Bounds']['Southwest'], 'ne'=>$record['Geometry']['Bounds']['Northeast']);
		}

		return $res;
	}

	/**
	 * fetches url with curl if available
	 * fallbacks: cake and php
	 * note: expects url with json encoded content
	 * @access private
	 **/
	function _fetch($url) {
		App::import('Lib', 'Tools.HttpSocketLib');
		$this->HttpSocket = new HttpSocketLib($this->use, 'CakePHP Geocode Lib');
		if ($res = $this->HttpSocket->fetch($url)) {
			return $res;
		}
		$this->setError($this->HttpSocket->error());
		return false;
	}

	/**
	* debugging
	* 2009-11-27 ms
	*/
	function debug() {
		return $this->result;
	}

	/**
	 * Calculates Distance between two points - each: array('lat'=>x,'lng'=>y)
	 * DB:
        '6371.04 * ACOS( COS( PI()/2 - RADIANS(90 - Retailer.lat)) * ' .
                        'COS( PI()/2 - RADIANS(90 - '. $data['Location']['lat'] .')) * ' .
                        'COS( RADIANS(Retailer.lng) - RADIANS('. $data['Location']['lng'] .')) + ' .
                        'SIN( PI()/2 - RADIANS(90 - Retailer.lat)) * ' .
                        'SIN( PI()/2 - RADIANS(90 - '. $data['Location']['lat'] . '))) ' .
        'AS distance'
	 * @param array pointX
	 * @param array pointY
	 * @param float $unit (M=miles, K=kilometers, N=nautical miles, I=inches, F=feet)
	 * @return int distance: in km
	 * 2009-03-06 ms
	 */
	function distance($pointX, $pointY, $unit = null) {
		if (empty($unit) || !array_key_exists(($unit = strtoupper($unit)), $this->units)) {
			$unit = array_keys($this->units);
			$unit = $unit[0];
		}

		/*
		$res = 	6371.04 * ACOS( COS( PI()/2 - rad2deg(90 - $pointX['lat'])) *
				COS( PI()/2 - rad2deg(90 - $pointY['lat'])) *
				COS( rad2deg($pointX['lng']) - rad2deg($pointY['lng'])) +
				SIN( PI()/2 - rad2deg(90 - $pointX['lat'])) *
				SIN( PI()/2 - rad2deg(90 - $pointY['lat'])));

		$res = 6371.04 * acos(sin($pointY['lat'])*sin($pointX['lat'])+cos($pointY['lat'])*cos($pointX['lat'])*cos($pointY['lng'] - $pointX['lng']));
		*/

		# seems to be the only working one (although slightly incorrect...)
		$res =  69.09 * rad2deg(acos(sin(deg2rad($pointX['lat'])) * sin(deg2rad($pointY['lat'])) +  cos(deg2rad($pointX['lat'])) * cos(deg2rad($pointY['lat'])) * cos(deg2rad($pointX['lng'] - $pointY['lng']))));
		if (isset($this->units[$unit])) {
			$res *= $this->units[$unit];
		}
		return ceil($res);
	}

	function convert($value, $fromUnit, $toUnit) {
		if (!isset($this->units[($fromUnit = strtoupper($fromUnit))]) || !isset($this->units[($toUnit = strtoupper($toUnit))])) {
			return false;
		}
		if ($fromUnit == 'M') {
			$value *= $this->units[$toUnit];
		} elseif ($toUnit == 'M') {
			$value /= $this->units[$fromUnit];
		} else {
			$value /= $this->units[$fromUnit];
			$value *= $this->units[$toUnit];
		}
		return $value;
	}

	const ACC_ROOFTOP = 'ROOFTOP';
	const ACC_RANGE_INTERPOLATED = 'RANGE_INTERPOLATED';
	const ACC_GEOMETRIC_CENTER = 'GEOMETRIC_CENTER';
	const ACC_APPROXIMATE = 'APPROXIMATE';

	const CODE_SUCCESS = 'OK'; //200;
	const CODE_TOO_MANY_QUERIES = 'OVER_QUERY_LIMIT'; //620;
	const CODE_BAD_REQUEST = 'REQUEST_DENIED'; //400;
	const CODE_MISSING_QUERY = 'INVALID_REQUEST';//601;
	const CODE_UNKNOWN_ADDRESS = 'ZERO_RESULTS'; //602;

	/*
	const CODE_SERVER_ERROR = 500;

	const CODE_UNAVAILABLE_ADDRESS = 603;
	const CODE_UNKNOWN_DIRECTIONS = 604;
	const CODE_BAD_KEY = 610;

	*/
}

/*

TODO:
http://code.google.com/intl/de-DE/apis/maps/documentation/geocoding/
- whats the difference to "http://maps.google.com/maps/api/geocode/output?parameters"

*/

/*

accuracy in v2 (deprecated in v3):
0 	Unbekannter Ort.
1 	Land.
2 	Bundesland/Bundesstaat, Provinz, Präfektur usw.
3 	Bezirk, Gemeinde usw.
4 	Ortschaft (Stadt, Dorf).
5 	Postleitzahl (PLZ).
6 	Straße.
7 	Kreuzung.
8 	Adresse.

*/

/*

Example: NEW:

Array
(
    [status] => OK
    [Result] => Array
        (
            [type] => postal_code
            [formatted_address] => 74523, Deutschland
            [AddressComponent] => Array
                (
                    [0] => Array
                        (
                            [long_name] => 74523
                            [short_name] => 74523
                            [type] => postal_code
                        )

                    [1] => Array
                        (
                            [long_name] => Schwäbisch Hall
                            [short_name] => SHA
                            [Type] => Array
                                (
                                    [0] => administrative_area_level_2
                                    [1] => political
                                )

                        )

                    [2] => Array
                        (
                            [long_name] => Baden-Württemberg
                            [short_name] => BW
                            [Type] => Array
                                (
                                    [0] => administrative_area_level_1
                                    [1] => political
                                )

                        )

                    [3] => Array
                        (
                            [long_name] => Deutschland
                            [short_name] => DE
                            [Type] => Array
                                (
                                    [0] => country
                                    [1] => political
                                )

                        )

                )

            [Geometry] => Array
                (
                    [Location] => Array
                        (
                            [lat] => 49.1257616
                            [lng] => 9.7544127
                        )

                    [location_type] => APPROXIMATE
                    [Viewport] => Array
                        (
                            [Southwest] => Array
                                (
                                    [lat] => 49.0451477
                                    [lng] => 9.6132550
                                )

                            [Northeast] => Array
                                (
                                    [lat] => 49.1670260
                                    [lng] => 9.8756350
                                )

                        )

                    [Bounds] => Array
                        (
                            [Southwest] => Array
                                (
                                    [lat] => 49.0451477
                                    [lng] => 9.6132550
                                )

                            [Northeast] => Array
                                (
                                    [lat] => 49.1670260
                                    [lng] => 9.8756350
                                )

                        )

                )

        )

)


Example OLD:

Array
(
    [name] => 74523 Deutschland
    [Status] => Array
        (
            [code] => 200
            [request] => geocode
        )

    [Result] => Array
        (
            [id] => p1
            [address] => 74523, Deutschland
            [AddressDetails] => Array
                (
                    [Accuracy] => 5
                    [xmlns] => urn:oasis:names:tc:ciq:xsdschema:xAL:2.0
                    [Country] => Array
                        (
                            [CountryNameCode] => DE
                            [CountryName] => Deutschland
                            [AdministrativeArea] => Array
                                (
                                    [AdministrativeAreaName] => Baden-Württemberg
                                    [SubAdministrativeArea] => Array
                                        (
                                            [SubAdministrativeAreaName] => Schwäbisch Hall
                                            [PostalCode] => Array
                                                (
                                                    [PostalCodeNumber] => 74523
                                                )

                                        )

                                )

                        )

                )

            [ExtendedData] => Array
                (
                    [LatLonBox] => Array
                        (
                            [north] => 49.1670260
                            [south] => 49.0451477
                            [east] => 9.8756350
                            [west] => 9.6132550
                        )

                )

            [Point] => Array
                (
                    [coordinates] => 9.7544127,49.1257616,0
                )

        )

)


{
  "status": "OK",
  "results": [ {
    "types": [ "street_address" ],
    "formatted_address": "Krebenweg 20, 74523 Schwäbisch Hall, Deutschland",
    "address_components": [ {
      "long_name": "20",
      "short_name": "20",
      "types": [ "street_number" ]
    }, {
      "long_name": "Krebenweg",
      "short_name": "Krebenweg",
      "types": [ "route" ]
    }, {
      "long_name": "Bibersfeld",
      "short_name": "Bibersfeld",
      "types": [ "sublocality", "political" ]
    }, {
      "long_name": "Schwäbisch Hall",
      "short_name": "Schwäbisch Hall",
      "types": [ "locality", "political" ]
    }, {
      "long_name": "Schwäbisch Hall",
      "short_name": "SHA",
      "types": [ "administrative_area_level_2", "political" ]
    }, {
      "long_name": "Baden-Württemberg",
      "short_name": "BW",
      "types": [ "administrative_area_level_1", "political" ]
    }, {
      "long_name": "Deutschland",
      "short_name": "DE",
      "types": [ "country", "political" ]
    }, {
      "long_name": "74523",
      "short_name": "74523",
      "types": [ "postal_code" ]
    } ],
    "geometry": {
      "location": {
        "lat": 49.0817369,
        "lng": 9.6908451
      },
      "location_type": "RANGE_INTERPOLATED", //ROOFTOP //APPROXIMATE
      "viewport": {
        "southwest": {
          "lat": 49.0785954,
          "lng": 9.6876999
        },
        "northeast": {
          "lat": 49.0848907,
          "lng": 9.6939951
        }
      },
      "bounds": {
        "southwest": {
          "lat": 49.0817369,
          "lng": 9.6908451
        },
        "northeast": {
          "lat": 49.0817492,
          "lng": 9.6908499
        }
      }
    },
    "partial_match": true
  } ]
}

*/
?>